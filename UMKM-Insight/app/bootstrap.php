<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_readable($path)) {
        require_once $path;
    }
});

require_once APP_ROOT . '/app/Support/helpers.php';

use App\Core\Database;
use App\Core\Env;

Env::load(APP_ROOT . '/.env');

$pdo = Database::connection();

