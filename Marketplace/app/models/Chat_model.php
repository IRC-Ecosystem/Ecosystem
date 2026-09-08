<?php

class Chat_model
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function send(int $senderId, int $receiverId, string $message, ?int $storeId = null): bool
    {
        $stmt = $this->db->prepare('INSERT INTO chats (sender_id, receiver_id, store_id, message) VALUES (?, ?, ?, ?)');
        return $stmt->execute([$senderId, $receiverId, $storeId, trim($message)]);
    }

    public function conversation(int $user1, int $user2): array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, u1.name sender_name, u2.name receiver_name
            FROM chats c
            JOIN users u1 ON u1.id = c.sender_id
            JOIN users u2 ON u2.id = c.receiver_id
            WHERE (c.sender_id = ? AND c.receiver_id = ?) OR (c.sender_id = ? AND c.receiver_id = ?)
            ORDER BY c.created_at ASC
        ');
        $stmt->execute([$user1, $user2, $user2, $user1]);
        return $stmt->fetchAll();
    }

    public function forUser(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT c.*, u1.name sender_name, u2.name receiver_name, u.name peer_name, u.role peer_role
            FROM chats c
            JOIN users u1 ON u1.id = c.sender_id
            JOIN users u2 ON u2.id = c.receiver_id
            JOIN users u ON u.id = IF(c.sender_id = ?, c.receiver_id, c.sender_id)
            WHERE c.sender_id = ? OR c.receiver_id = ?
            ORDER BY c.created_at ASC
        ');
        $stmt->execute([$userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function contactsForUser(int $userId): array
    {
        $stmt = $this->db->prepare('
            SELECT u.id, u.name, u.role, s.name store_name
            FROM users u
            LEFT JOIN stores s ON s.owner_id = u.id
            WHERE u.id != ? AND (u.role IN ("seller", "admin") OR u.id IN (
                SELECT IF(sender_id = ?, receiver_id, sender_id) FROM chats WHERE sender_id = ? OR receiver_id = ?
            ))
            ORDER BY u.role DESC, u.name ASC
        ');
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }
}
