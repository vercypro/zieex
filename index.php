<?php

declare(strict_types=1);

define('ZIEEX_START', microtime(true));
define('BASE_PATH', __DIR__);

// PHP 8.2+ required
if (PHP_VERSION_ID < 80200) {
    die('Zieex requires PHP 8.2 or higher. Current version: ' . PHP_VERSION);
}

// Autoloader
if (!file_exists(BASE_PATH . '/vendor/autoload.php')) {
    die('Please run <b>composer install</b> first.');
}

require_once BASE_PATH . '/vendor/autoload.php';

// Check if installed
if (!file_exists(BASE_PATH . '/.env') && !file_exists(BASE_PATH . '/install/.installed')) {
    require_once BASE_PATH . '/install/installer.php';
    exit;
}

// Boot the application
$app = new Zieex\Application();
$app->boot();
$app->run();
