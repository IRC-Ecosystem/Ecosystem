<?php
/**
 * Auth Middleware
 * Menangani session dan proteksi halaman berdasarkan role
 */
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role) {
        // Redirect ke dashboard masing-masing jika role tidak sesuai
        if ($_SESSION['role'] === 'admin') header("Location: admin.php");
        elseif ($_SESSION['role'] === 'operator') header("Location: operator.php");
        else header("Location: dashboard.php");
        exit();
    }
}

function getCurrentUser($pdo) {
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}
?>
