<?php

declare(strict_types=1);

namespace MyVendor\Cms\Integration;

use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/**
 * Base for tests that exercise the real-DB Read/Write path.
 *
 * Setup: needs a running MySQL on the DSN in MYSQL_TEST_DSN (or the default
 * `mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4`). If the
 * server is not reachable the entire suite is skipped — keeps `composer test`
 * green on machines without docker / malt while still running everywhere
 * MySQL is up.
 *
 * Workflow on each setUp:
 *   1) connect, fail-fast skip if unreachable
 *   2) drop and recreate the schema (doctrine-migrations migrate:fresh-equivalent)
 *   3) seed from var/fake/*.json via bin/seed.php logic
 */
abstract class AbstractMySQLTestCase extends TestCase
{
    protected ResourceInterface $resource;
    protected PDO $pdo;

    protected function setUp(): void
    {
        $dsn = (string) (getenv('MYSQL_TEST_DSN') ?: 'mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4');
        $user = (string) (getenv('MYSQL_TEST_USER') ?: 'root');
        $password = (string) (getenv('MYSQL_TEST_PASSWORD') ?: 'root');

        try {
            $this->pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            $this->markTestSkipped(sprintf('MySQL not reachable at %s: %s', $dsn, $e->getMessage()));
        }

        // Set env vars for the BEAR app so AppModule's AuraSqlModule sees them.
        putenv('DB_DSN=' . $dsn);
        putenv('DB_USER=' . $user);
        putenv('DB_PASSWORD=' . $password);

        $this->migrateAndSeed();

        // Use 'hal-api-app' to exercise the real SqlQuery path (not Fake).
        // Fresh injector each test so env vars and schema state are picked up.
        $injector = Injector::getInstance('hal-api-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    private function migrateAndSeed(): void
    {
        // Drop and recreate all tables to start clean.
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['article_tags', 'articles', 'tags', 'categories', 'authors', 'media', 'doctrine_migration_versions'] as $t) {
            $this->pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $t));
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // Run doctrine-migrations migrate
        $cmd = sprintf(
            'cd %s && DB_DSN=%s DB_USER=%s DB_PASSWORD=%s vendor/bin/doctrine-migrations migrate --no-interaction 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg(getenv('DB_DSN')),
            escapeshellarg(getenv('DB_USER')),
            escapeshellarg(getenv('DB_PASSWORD')),
        );
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $this->fail('doctrine-migrations migrate failed: ' . implode("\n", $output));
        }

        // Seed via bin/seed.php
        $cmd = sprintf(
            'cd %s && DB_DSN=%s DB_USER=%s DB_PASSWORD=%s php bin/seed.php 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg(getenv('DB_DSN')),
            escapeshellarg(getenv('DB_USER')),
            escapeshellarg(getenv('DB_PASSWORD')),
        );
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $this->fail('bin/seed.php failed: ' . implode("\n", $output));
        }
    }
}
