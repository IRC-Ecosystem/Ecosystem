<?php

class Product_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function latest(?string $keyword = null, ?float $minPrice = null, ?float $maxPrice = null): array
    {
        $where = ['p.status = "active"'];
        $params = [];

        if ($keyword) {
            $like = '%' . $keyword . '%';
            $where[] = '(p.name LIKE ? OR p.category LIKE ? OR s.name LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($minPrice !== null) {
            $where[] = 'p.price >= ?';
            $params[] = $minPrice;
        }

        if ($maxPrice !== null) {
            $where[] = 'p.price <= ?';
            $params[] = $maxPrice;
        }

        $stmt = $this->db->prepare('SELECT p.*, s.name store_name FROM products p JOIN stores s ON s.id = p.store_id WHERE ' . implode(' AND ', $where) . ' ORDER BY p.created_at DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function allCategories(): array
    {
        return $this->db->query('SELECT DISTINCT category FROM products WHERE status = "active" ORDER BY category ASC')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT p.*, s.name store_name, s.owner_id, s.smartbank_external_id FROM products p JOIN stores s ON s.id = p.store_id WHERE p.id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function byStore(int $storeId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE store_id = ? ORDER BY created_at DESC');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function categoriesByStore(int $storeId): array
    {
        $stmt = $this->db->prepare('SELECT category, COUNT(*) total FROM products WHERE store_id = ? GROUP BY category ORDER BY category');
        $stmt->execute([$storeId]);
        return $stmt->fetchAll();
    }

    public function lowStockByStore(int $storeId, int $limit = 5): array
    {
        $stmt = $this->db->prepare('SELECT * FROM products WHERE store_id = ? AND stock <= ? ORDER BY stock ASC, name ASC');
        $stmt->execute([$storeId, $limit]);
        return $stmt->fetchAll();
    }

    public function lowStockGlobal(int $limit = 10, int $threshold = 10): array
    {
        $stmt = $this->db->prepare('
            SELECT p.*, s.name store_name, s.owner_id
            FROM products p
            JOIN stores s ON s.id = p.store_id
            WHERE p.stock <= ?
            ORDER BY p.stock ASC, p.name ASC
            LIMIT ?
        ');
        $stmt->bindValue(1, $threshold, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function featured(int $limit = 4): array
    {
        $stmt = $this->db->prepare('SELECT p.*, s.name store_name FROM products p JOIN stores s ON s.id = p.store_id WHERE p.status = "active" ORDER BY p.stock DESC, p.created_at DESC LIMIT ?');
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function create(int $storeId, array $data): bool
    {
        $stmt = $this->db->prepare('INSERT INTO products (store_id, name, category, description, price, stock, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
        return $stmt->execute([
            $storeId,
            trim($data['name']),
            trim($data['category']),
            trim($data['description'] ?? ''),
            (float) $data['price'],
            (int) $data['stock'],
            trim($data['image_url'] ?? ''),
        ]);
    }

    public function update(int $id, int $storeId, array $data): bool
    {
        $stmt = $this->db->prepare('UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?, image_url = ?, status = ? WHERE id = ? AND store_id = ?');
        return $stmt->execute([
            trim($data['name']),
            trim($data['category']),
            trim($data['description'] ?? ''),
            (float) $data['price'],
            (int) $data['stock'],
            trim($data['image_url'] ?? ''),
            $data['status'] ?? 'active',
            $id,
            $storeId,
        ]);
    }

    public function delete(int $id, int $storeId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM products WHERE id = ? AND store_id = ?');
        return $stmt->execute([$id, $storeId]);
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM products')->fetchColumn();
    }

    public function countByStore(): array
    {
        return $this->db->query('SELECT store_id, COUNT(*) total FROM products GROUP BY store_id')->fetchAll();
    }

    public function lowStockCount(int $limit = 5): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM products WHERE stock <= ?');
        $stmt->execute([$limit]);
        return (int) $stmt->fetchColumn();
    }
}
