<?php

class Order_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function checkout(int $userId, string $address, array $summary): ?int
    {
        if (empty($summary['items'])) {
            return null;
        }

        $inTransaction = $this->db->inTransaction();
        if (!$inTransaction) {
            $this->db->beginTransaction();
        }

        try {
            $orderCode = 'PK-' . date('YmdHis') . '-' . random_int(100, 999);
            $stmt = $this->db->prepare('INSERT INTO orders (user_id, order_code, shipping_address, subtotal, voucher_discount, marketplace_fee, gateway_fee, bank_fee, tax, shipping_fee, total, payment_status, order_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "pending", "processing")');
            $stmt->execute([
                $userId,
                $orderCode,
                $address,
                $summary['subtotal'],
                $summary['discount'] ?? 0,
                $summary['marketplaceFee'],
                $summary['gatewayFee'],
                $summary['bankFee'],
                $summary['tax'],
                $summary['shipping'],
                $summary['total'],
            ]);
            $orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare('INSERT INTO order_items (order_id, product_id, store_id, product_name, price, qty, subtotal, item_status) VALUES (?, ?, ?, ?, ?, ?, ?, "processing")');
            $stockStmt = $this->db->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
            foreach ($summary['items'] as $item) {
                $product = $item['product'];
                $itemStmt->execute([$orderId, $product['id'], $product['store_id'], $product['name'], $product['price'], $item['qty'], $item['subtotal']]);

                $stockStmt->execute([$item['qty'], $product['id'], $item['qty']]);
                if ($stockStmt->rowCount() === 0) {
                    throw new RuntimeException('Stok produk "' . $product['name'] . '" tidak mencukupi.');
                }
            }

            $this->db->prepare('INSERT INTO payment_requests (order_id, from_app, user_id, amount, status, metadata) VALUES (?, "PasarKita", ?, ?, "pending", ?)')->execute([
                $orderId,
                $userId,
                $summary['total'],
                json_encode(['gateway' => 'smartbank_connector']),
            ]);

            if (!$inTransaction) {
                $this->db->commit();
            }
            return $orderId;
        } catch (Throwable $e) {
            if (!$inTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function checkoutStores(int $userId, string $address, array $summary): array
    {
        $this->db->beginTransaction();
        try {
            $orderIds = [];
            foreach ($summary['storeSummaries'] ?? [] as $storeSummary) {
                $orderId = $this->checkout($userId, $address, $storeSummary);
                if (!$orderId) {
                    $this->db->rollBack();
                    return [];
                }

                $stmt = $this->db->prepare('UPDATE orders SET store_id = ?, seller_external_id = ? WHERE id = ?');
                $stmt->execute([$storeSummary['storeId'], $storeSummary['sellerExternalId'], $orderId]);
                $orderIds[] = $orderId;
            }

            $this->db->commit();
            return $orderIds;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [];
        }
    }

    public function byUser(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function findByUser(int $orderId, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$orderId, $userId]);
        return $stmt->fetch() ?: null;
    }

    public function markSmartBankPaid(int $orderId, int $userId, array $result): void
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE orders SET payment_status = "paid" WHERE id = ? AND user_id = ? AND payment_status = "pending"');
            $stmt->execute([$orderId, $userId]);

            if ($stmt->rowCount() === 0) {
                // Idempotent guard: sudah paid atau order tidak valid
                $this->db->commit();
                return;
            }

            $this->db->prepare('UPDATE payment_requests SET status = "success", metadata = ? WHERE order_id = ?')->execute([json_encode($result), $orderId]);
            $this->db->prepare('INSERT INTO ledgers (user_id, order_id, type, amount, description) SELECT user_id, id, "debit", total, CONCAT("SmartBank PasarKita ", order_code) FROM orders WHERE id = ? AND user_id = ?')->execute([$orderId, $userId]);

            // Outbox 1: Logistika shipment creation
            $this->db->prepare('INSERT INTO integration_outbox (event_id, event_type, aggregate_id, payload) SELECT UUID(), "MARKETPLACE_ORDER_PAID", id, JSON_OBJECT("order_id",id,"order_code",order_code,"origin","Marketplace Warehouse","destination",shipping_address) FROM orders WHERE id=?')->execute([$orderId]);

            // Outbox 2: UMKM Insight event publishing
            $this->db->prepare('INSERT INTO integration_outbox (event_id, event_type, aggregate_id, payload) SELECT UUID(), "UMKM_INSIGHT_PAYMENT_SETTLED", id, JSON_OBJECT("order_id",id,"order_code",order_code,"amount",total,"occurred_at",NOW()) FROM orders WHERE id=?')->execute([$orderId]);

            $this->db->commit();
        } catch (Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }

    public function cancelOrder(int $orderId, int $userId): bool
    {
        $this->db->beginTransaction();
        try {
            $order = $this->findByUser($orderId, $userId);
            if (!$order || $order['order_status'] === 'completed' || $order['order_status'] === 'cancelled') {
                $this->db->rollBack();
                return false;
            }

            // Restore stocks
            $itemsStmt = $this->db->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ?');
            $itemsStmt->execute([$orderId]);
            $restoreStmt = $this->db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            foreach ($itemsStmt->fetchAll() as $item) {
                $restoreStmt->execute([$item['qty'], $item['product_id']]);
            }

            $this->db->prepare('UPDATE orders SET order_status = "cancelled" WHERE id = ?')->execute([$orderId]);
            $this->db->prepare('UPDATE order_items SET item_status = "cancelled" WHERE order_id = ?')->execute([$orderId]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function itemsByUser(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT oi.*, p.image_url, p.category, o.user_id
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE o.user_id = ?
            ORDER BY oi.id ASC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function byStore(int $storeId): array
    {
        $stmt = $this->db->prepare('SELECT DISTINCT o.*, u.name customer_name FROM orders o JOIN order_items oi ON oi.order_id = o.id JOIN users u ON u.id = o.user_id WHERE oi.store_id = ? ORDER BY o.created_at DESC');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function itemsByStore(int $storeId): array
    {
        $stmt = $this->db->prepare('SELECT oi.*, o.order_code, o.order_status global_order_status, o.payment_status, o.created_at FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE oi.store_id = ? ORDER BY o.created_at DESC');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function sellerSummary(int $storeId): array
    {
        $stmt = $this->db->prepare('
            SELECT
                COALESCE(SUM(CASE WHEN DATE(o.created_at) = CURDATE() THEN oi.subtotal ELSE 0 END), 0) omzet_hari_ini,
                COALESCE(SUM(CASE WHEN YEAR(o.created_at) = YEAR(CURDATE()) AND MONTH(o.created_at) = MONTH(CURDATE()) THEN oi.subtotal ELSE 0 END), 0) omzet_bulan_ini,
                COUNT(DISTINCT CASE WHEN oi.item_status IN ("processing", "shipped") THEN oi.id END) pesanan_aktif,
                COUNT(DISTINCT CASE WHEN oi.item_status = "processing" THEN oi.id END) pesanan_baru,
                COUNT(DISTINCT CASE WHEN oi.item_status = "completed" THEN oi.id END) pesanan_selesai,
                COUNT(DISTINCT CASE WHEN oi.item_status = "cancelled" THEN oi.id END) pesanan_batal,
                COALESCE(SUM(oi.subtotal), 0) total_pendapatan,
                COALESCE(SUM(oi.subtotal * 0.02), 0) total_fee_marketplace
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE oi.store_id = ?
        ');
        $stmt->execute([$storeId]);
        return $stmt->fetch() ?: [];
    }

    public function bestSellersByStore(int $storeId, int $limit = 5): array
    {
        $stmt = $this->db->prepare('
            SELECT oi.product_id, oi.product_name, SUM(oi.qty) qty_sold, SUM(oi.subtotal) revenue
            FROM order_items oi
            WHERE oi.store_id = ?
            GROUP BY oi.product_id, oi.product_name
            ORDER BY qty_sold DESC, revenue DESC
            LIMIT ?
        ');
        $stmt->bindValue(1, $storeId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function all(): array
    {
        return $this->db->query('
            SELECT
                o.*,
                u.name customer_name,
                COALESCE(store_summary.store_names, "-") store_names,
                COALESCE(store_summary.item_count, 0) item_count
            FROM orders o
            JOIN users u ON u.id = o.user_id
            LEFT JOIN (
                SELECT
                    oi.order_id,
                    GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ", ") store_names,
                    SUM(oi.qty) item_count
                FROM order_items oi
                JOIN stores s ON s.id = oi.store_id
                GROUP BY oi.order_id
            ) store_summary ON store_summary.order_id = o.id
            ORDER BY o.created_at DESC
        ')->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    }

    public function revenue(): float
    {
        return (float) $this->db->query('SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = "paid"')->fetchColumn();
    }

    public function storeRevenueSummary(): array
    {
        return $this->db->query('
            SELECT
                oi.store_id,
                COALESCE(SUM(oi.subtotal), 0) revenue,
                COUNT(DISTINCT oi.order_id) orders
            FROM order_items oi
            JOIN orders o ON o.id = oi.order_id
            WHERE o.payment_status = "paid"
            GROUP BY oi.store_id
        ')->fetchAll();
    }

    public function updateStatus(int $orderId, string $status, int $storeId): bool
    {
        $allowed = ['processing', 'shipped', 'completed', 'cancelled'];
        if (!in_array($status, $allowed, true)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            // Update items for this store
            $stmt = $this->db->prepare('UPDATE order_items SET item_status = ? WHERE order_id = ? AND store_id = ?');
            $stmt->execute([$status, $orderId, $storeId]);

            // Update main order status if order belongs to this store
            $this->db->prepare('UPDATE orders SET order_status = ? WHERE id = ? AND store_id = ?')->execute([$status, $orderId, $storeId]);

            // Handle stock refund if cancelled
            if ($status === 'cancelled') {
                $itemsStmt = $this->db->prepare('SELECT product_id, qty FROM order_items WHERE order_id = ? AND store_id = ?');
                $itemsStmt->execute([$orderId, $storeId]);
                $restoreStmt = $this->db->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
                foreach ($itemsStmt->fetchAll() as $item) {
                    $restoreStmt->execute([$item['qty'], $item['product_id']]);
                }
            }

            // Handle seller payout release if completed
            if ($status === 'completed') {
                $payoutStmt = $this->db->prepare('SELECT SUM(subtotal) subtotal FROM order_items WHERE order_id = ? AND store_id = ?');
                $payoutStmt->execute([$orderId, $storeId]);
                $row = $payoutStmt->fetch();
                $subtotal = (float) ($row['subtotal'] ?? 0);

                if ($subtotal > 0) {
                    $fee = round($subtotal * 0.02);
                    $net = $subtotal - $fee;

                    // Insert payout record
                    $this->db->prepare('INSERT INTO seller_payouts (store_id, order_id, amount, marketplace_fee, net_amount, status) VALUES (?, ?, ?, ?, ?, "released")')->execute([
                        $storeId, $orderId, $subtotal, $fee, $net
                    ]);

                    // Credit store owner wallet
                    $ownerStmt = $this->db->prepare('SELECT owner_id FROM stores WHERE id = ?');
                    $ownerStmt->execute([$storeId]);
                    $ownerId = (int) $ownerStmt->fetchColumn();

                    if ($ownerId) {
                        $this->db->prepare('UPDATE wallets SET balance = balance + ? WHERE user_id = ?')->execute([$net, $ownerId]);
                        $this->db->prepare('INSERT INTO ledgers (user_id, order_id, type, amount, description) VALUES (?, ?, "credit", ?, "Payout Penjualan Order PasarKita")')->execute([
                            $ownerId, $orderId, $net
                        ]);
                    }
                }
            }

            // Sync global order_status if all items are completed or cancelled
            $allCompleted = $this->db->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_status != "completed"');
            $allCompleted->execute([$orderId]);
            if ((int) $allCompleted->fetchColumn() === 0) {
                $this->db->prepare('UPDATE orders SET order_status = "completed" WHERE id = ?')->execute([$orderId]);
            }

            $allCancelled = $this->db->prepare('SELECT COUNT(*) FROM order_items WHERE order_id = ? AND item_status != "cancelled"');
            $allCancelled->execute([$orderId]);
            if ((int) $allCancelled->fetchColumn() === 0) {
                $this->db->prepare('UPDATE orders SET order_status = "cancelled" WHERE id = ?')->execute([$orderId]);
            }

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }
}

