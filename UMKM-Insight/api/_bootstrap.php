<?php
error_reporting(E_ERROR);

require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-API-Key');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function apiResponse($success, $message, $data = null, $statusCode = 200, $error = null) {
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'status' => $success ? 'success' : 'error',
        'message' => $message,
        'data' => $data,
        'error' => $error,
        'meta' => [
            'service' => 'umkm-insight',
            'timestamp' => gmdate('c')
        ]
    ]);
    exit;
}

function readJsonBody() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        apiResponse(false, 'Body harus berupa JSON valid.', null, 400, [
            'code' => 'INVALID_JSON',
            'details' => json_last_error_msg()
        ]);
    }
    return is_array($data) ? $data : [];
}

function requireMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        apiResponse(false, 'Method tidak diizinkan.', null, 405, [
            'code' => 'METHOD_NOT_ALLOWED',
            'allowed' => [$method]
        ]);
    }
}

function requireApiKeyIfConfigured() {
    $expected = getenv('UMKM_API_KEY') ?: '';
    if ($expected === '') return;

    $provided = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if (!hash_equals($expected, $provided)) {
        apiResponse(false, 'API key tidak valid.', null, 401, [
            'code' => 'UNAUTHORIZED'
        ]);
    }
}

function findUserId(PDO $pdo, array $payload) {
    if (!empty($payload['user_id'])) {
        $rawUserId = (string) $payload['user_id'];
        if (ctype_digit($rawUserId)) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int) $rawUserId]);
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$rawUserId]);
        }
        $row = $stmt->fetch();
        if ($row) return (int) $row['id'];
    }

    if (!empty($payload['username'])) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$payload['username']]);
        $row = $stmt->fetch();
        if ($row) return (int) $row['id'];
    }

    if (!empty($payload['smartbank_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE smartbank_id = ? LIMIT 1');
        $stmt->execute([$payload['smartbank_id']]);
        $row = $stmt->fetch();
        if ($row) return (int) $row['id'];
    }

    if (!empty($payload['warungpos_id'])) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE warungpos_id = ? LIMIT 1');
        $stmt->execute([$payload['warungpos_id']]);
        $row = $stmt->fetch();
        if ($row) return (int) $row['id'];
    }

    return null;
}

function normalizeDateTime($value) {
    if (!$value) return date('Y-m-d H:i:s');
    $ts = strtotime($value);
    return $ts ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
}
?>
