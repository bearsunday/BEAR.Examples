<?php

declare(strict_types=1);

/**
 * `composer demo:variations` — contrast-only demo for the three Article GET
 * variations. Uses a fresh SQLite database so raw PDO and SqlQuery both run
 * through the real DB path without changing the main `composer demo` flow.
 */

use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;

require dirname(__DIR__) . '/autoload.php';

$root = dirname(__DIR__);
$db = '/tmp/bear_cms_variations.db';
$dsn = 'sqlite:' . $db;
$migrations = escapeshellarg($root . '/vendor/bin/doctrine-migrations');
$seed = escapeshellarg($root . '/bin/seed.php');
$tmpDir = escapeshellarg($root . '/var/tmp/hal-api-app');

function section(string $title): void
{
    fwrite(STDOUT, "\n{$title}\n");
    fwrite(STDOUT, str_repeat('-', strlen($title)) . "\n");
}

function run(string $cmd): void
{
    fwrite(STDOUT, "$ {$cmd}\n");
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        throw new RuntimeException("Command failed ({$exitCode}): {$cmd}");
    }
}

function tailOutput(string $cmd): string
{
    return 'bash -c ' . escapeshellarg('set -o pipefail; ' . $cmd . ' 2>&1 | tail -3');
}

@unlink($db);
putenv("DB_DSN={$dsn}");
putenv('DB_USER=');
putenv('DB_PASSWORD=');

section('Prepare SQLite');
run(tailOutput('DB_DSN=' . escapeshellarg($dsn) . ' ' . $migrations . ' migrate --no-interaction'));
run(tailOutput('DB_DSN=' . escapeshellarg($dsn) . ' ' . escapeshellarg(PHP_BINARY) . ' ' . $seed));
run('rm -rf ' . $tmpDir);

$resource = Injector::getInstance('hal-api-app')->getInstance(ResourceInterface::class);
assert($resource instanceof ResourceInterface);

section('Article GET variations');
$targets = [
    'array' => 'app://self/variations/articleasarray',
    'raw-pdo' => 'app://self/variations/articlerawpdo',
    'sql-query' => 'app://self/variations/articlesqlquery',
];

foreach ($targets as $label => $uri) {
    $ro = $resource->get($uri, ['id' => 2]);
    $body = $ro->body;
    fwrite(STDOUT, "{$label}: {$ro->code} {$body['title']}\n");
    if (! isset($body['readingTimeMinutes'])) {
        continue;
    }

    fwrite(STDOUT, "  readingTimeMinutes: {$body['readingTimeMinutes']}\n");
    fwrite(STDOUT, '  previous: ' . ($body['previous']['title'] ?? '(none)') . "\n");
    fwrite(STDOUT, '  next: ' . ($body['next']['title'] ?? '(none)') . "\n");
}
