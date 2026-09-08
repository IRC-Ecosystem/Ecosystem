<?php 

define('APP_ROOT', __DIR__);

require_once 'config/koneksi.php';
require_once 'core/Database.php';
require_once 'core/App.php';
require_once 'core/Controllers.php';
require_once 'core/Auth.php';

spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/models/' . $class . '.php',
        __DIR__ . '/core/' . $class . '.php',
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

?>
