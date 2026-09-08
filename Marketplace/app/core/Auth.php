<?php

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . csrf_token() . '">';
}

function verify_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals(csrf_token(), (string) $token)) {
            http_response_code(403);
            die('Invalid CSRF Token');
        }
    }
}

function check_rate_limit(string $key, int $maxAttempts = 5, int $decaySeconds = 300): bool
{
    $attempts = $_SESSION['rate_limits'][$key] ?? [];
    $currentTime = time();
    $attempts = array_filter($attempts, fn($timestamp) => ($currentTime - $timestamp) < $decaySeconds);
    $_SESSION['rate_limits'][$key] = $attempts;
    return count($attempts) < $maxAttempts;
}

function hit_rate_limit(string $key): void
{
    $_SESSION['rate_limits'][$key][] = time();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function has_role(array|string $roles): bool
{
    $user = current_user();
    $roles = is_array($roles) ? $roles : [$roles];
    return $user && in_array($user['role'], $roles, true);
}

function role_home(?array $user = null): string
{
    $user = $user ?? current_user();

    return match ($user['role'] ?? null) {
        'admin' => 'admin',
        'seller' => 'toko',
        default => 'user',
    };
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . BASEURL . 'auth/login');
        exit;
    }
}

function require_role(array|string $roles): void
{
    require_login();
    if (!has_role($roles)) {
        header('Location: ' . BASEURL . role_home());
        exit;
    }
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

