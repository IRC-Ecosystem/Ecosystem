<?php

declare(strict_types=1);

if (!function_exists('sanitize')) {
    function sanitize($data): string
    {
        return htmlspecialchars(strip_tags(trim((string) $data)), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('formatRupiah')) {
    function formatRupiah($angka): string
    {
        return 'Rp ' . number_format((float) $angka, 0, ',', '.');
    }
}

if (!function_exists('uiInitials')) {
    function uiInitials($name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name));
        $first = $parts[0][0] ?? 'U';
        $second = $parts[1][0] ?? '';
        return strtoupper($first . $second);
    }
}

if (!function_exists('redirectTo')) {
    function redirectTo(string $path): void
    {
        header('Location: ' . $path);
        exit;
    }
}

if (!function_exists('maskId')) {
    function maskId($id): string
    {
        $id = (string) $id;
        if ($id === '') return '';
        if (strlen($id) <= 4) return str_repeat('*', strlen($id));
        return substr($id, 0, 2) . str_repeat('*', strlen($id) - 4) . substr($id, -2);
    }
}
