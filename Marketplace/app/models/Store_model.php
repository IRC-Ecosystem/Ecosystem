<?php

class Store_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function findByOwner(int $ownerId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM stores WHERE owner_id = ? LIMIT 1');
        $stmt->execute([$ownerId]);
        return $stmt->fetch() ?: null;
    }

    public function create(int $ownerId, array $data): bool
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO stores (owner_id, name, description, address, status) VALUES (?, ?, ?, ?, "active")');
            $stmt->execute([$ownerId, trim($data['name']), trim($data['description'] ?? ''), trim($data['address'] ?? '')]);
            $this->db->prepare('UPDATE users SET role = "seller" WHERE id = ? AND role = "user"')->execute([$ownerId]);
            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function setSmartBankExternalId(int $storeId, string $externalId): void
    {
        $stmt = $this->db->prepare('UPDATE stores SET smartbank_external_id = ? WHERE id = ?');
        $stmt->execute([$externalId, $storeId]);
    }

    public function all(): array
    {
        return $this->db->query('SELECT s.*, u.name owner_name FROM stores s JOIN users u ON u.id = s.owner_id ORDER BY s.created_at DESC')->fetchAll();
    }
}
