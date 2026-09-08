<?php
class Database {
    private $host;
    private $user;
    private $pass;
    private $db_name;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: 'mysql';
        $this->user = getenv('DB_USER') ?: 'logistika_app';
        $this->pass = getenv('DB_PASSWORD') ?: '';
        $this->db_name = getenv('DB_NAME') ?: 'logistika';
    }

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);
            if ($this->conn->connect_error) {
                die(json_encode(["status" => "error", "message" => "Connection failed: " . $this->conn->connect_error]));
            }
        } catch(Exception $e) {
            echo json_encode(["status" => "error", "message" => "Connection error: " . $e->getMessage()]);
        }
        return $this->conn;
    }
}
?>
