<?php

declare(strict_types=1);

use Doctrine\DBAL\DriverManager;

$envPath = __DIR__ . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        if ($k !== '' && getenv($k) === false) {
            putenv(sprintf('%s=%s', $k, $v));
        }
    }
}

$dsn = getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4';
$params = ['driver' => 'pdo_mysql', 'charset' => 'utf8mb4'];
$driverPrefix = strtok($dsn, ':');
if ($driverPrefix === 'sqlite') {
    $params['driver'] = 'pdo_sqlite';
    $params['path'] = (string) substr($dsn, 7);
} else {
    foreach (explode(';', (string) substr($dsn, strlen($driverPrefix) + 1)) as $pair) {
        if (! str_contains($pair, '=')) {
            continue;
        }

        [$k, $v] = explode('=', $pair, 2);
        $params[$k] = $v;
    }
}

$params['user'] = getenv('DB_USER') ?: 'root';
$params['password'] = getenv('DB_PASSWORD') ?: '';

return DriverManager::getConnection($params);
