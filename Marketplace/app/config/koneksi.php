<?php

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if (
            (substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")
        ) {
            $value = substr($value, 1, -1);
        }

        if (getenv($key) !== false) {
            continue;
        }

        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

function env_value(string $key, $default = null)
{
    $value = $_ENV[$key] ?? getenv($key);
    return $value === false || $value === null ? $default : $value;
}

load_env(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');

define('APP_NAME', env_value('APP_NAME', 'PasarKita'));
define('APP_ENV', env_value('APP_ENV', 'local'));
define('APP_DEBUG', filter_var(env_value('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN));
define('BASEURL', rtrim(env_value('BASEURL', 'http://localhost:8080/'), '/') . '/');
define('DB_HOST', env_value('DB_HOST', 'localhost'));
define('DB_USER', env_value('DB_USER', 'root'));
define('DB_PASS', env_value('DB_PASS', ''));
define('DB_NAME', env_value('DB_NAME', 'pasarkita'));
define('SMARTBANK_CONNECTOR_URL', rtrim(env_value('SMARTBANK_CONNECTOR_URL', 'http://localhost:5000'), '/'));
define('SMARTBANK_CONNECTOR_API_KEY', env_value('SMARTBANK_CONNECTOR_API_KEY', ''));
define('SMARTBANK_MARKETPLACE_EXTERNAL_ID', env_value('SMARTBANK_MARKETPLACE_EXTERNAL_ID', 'marketplace-merchant-main'));
define('SMARTBANK_CONNECTOR_TIMEOUT_MS', (int) env_value('SMARTBANK_CONNECTOR_TIMEOUT_MS', 10000));
define('LOGISTIKA_URL', rtrim(env_value('LOGISTIKA_URL', ''), '/'));
define('LOGISTIKA_API_KEY', env_value('LOGISTIKA_API_KEY', ''));
define('INTEGRATION_WEBHOOK_SECRET', env_value('INTEGRATION_WEBHOOK_SECRET', ''));
define('UMKM_INSIGHT_EVENTS_URL', rtrim(env_value('UMKM_INSIGHT_EVENTS_URL', ''), '/'));
define('UMKM_INSIGHT_EVENTS_API_KEY', env_value('UMKM_INSIGHT_EVENTS_API_KEY', ''));

function log_app_error(Throwable $e): void
{
    $logFile = dirname(__DIR__) . '/logs/error.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $message = date('[Y-m-d H:i:s] ') . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString() . "\n\n";
    @file_put_contents($logFile, $message, FILE_APPEND);
}
