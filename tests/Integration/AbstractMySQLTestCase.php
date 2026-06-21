<?php

declare(strict_types=1);

namespace BEAR\Kata\Integration;

use BEAR\Resource\ResourceInterface;
use BEAR\Kata\Injector;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function getenv;
use function implode;
use function putenv;
use function sprintf;

use const PHP_BINARY;

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
    /** @var array<string, string|false> */
    private array $previousEnv = [];

    protected ResourceInterface $resource;
    protected PDO $pdo;

    protected function setUp(): void
    {
        $dsn = getenv('MYSQL_TEST_DSN') !== false ? (string) getenv('MYSQL_TEST_DSN') : 'mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4';
        $user = getenv('MYSQL_TEST_USER') !== false ? (string) getenv('MYSQL_TEST_USER') : 'root';
        $password = getenv('MYSQL_TEST_PASSWORD') !== false ? (string) getenv('MYSQL_TEST_PASSWORD') : '';

        try {
            $this->pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            $this->markTestSkipped(sprintf('MySQL not reachable at %s: %s', $dsn, $e->getMessage()));
        }

        // Snapshot prior values so tearDown() can restore them; otherwise these
        // putenv calls leak DB_* into subsequent (non-MySQL) suites in the same process.
        foreach (['DB_DSN', 'DB_USER', 'DB_PASSWORD'] as $name) {
            $this->previousEnv[$name] = getenv($name);
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

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $name => $value) {
            putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
        }

        $this->previousEnv = [];
    }

    private function migrateAndSeed(): void
    {
        // Drop and recreate all tables to start clean.
        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        foreach (['article_tags', 'articles', 'tags', 'categories', 'auth_identities', 'authors', 'media', 'doctrine_migration_versions'] as $t) {
            $this->pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $t));
        }

        $this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

        // Run doctrine-migrations migrate. Pin PHP_BINARY so the subprocess
        // uses the same interpreter as the running test (otherwise composer's
        // platform_check.php fires when the system `php` is older than the
        // composer.json `php` requirement).
        $cmd = sprintf(
            'cd %s && DB_DSN=%s DB_USER=%s DB_PASSWORD=%s %s vendor/bin/doctrine-migrations migrate --no-interaction 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg(getenv('DB_DSN')),
            escapeshellarg(getenv('DB_USER')),
            escapeshellarg(getenv('DB_PASSWORD')),
            escapeshellarg(PHP_BINARY),
        );
        exec($cmd, $output, $code);
        if ($code !== 0) {
            $this->fail('doctrine-migrations migrate failed: ' . implode("\n", $output));
        }

        // Seed via bin/seed.php (same PHP_BINARY pinning as above).
        $cmd = sprintf(
            'cd %s && DB_DSN=%s DB_USER=%s DB_PASSWORD=%s %s bin/seed.php 2>&1',
            escapeshellarg(dirname(__DIR__, 2)),
            escapeshellarg(getenv('DB_DSN')),
            escapeshellarg(getenv('DB_USER')),
            escapeshellarg(getenv('DB_PASSWORD')),
            escapeshellarg(PHP_BINARY),
        );
        exec($cmd, $output, $code);
        if ($code === 0) {
            return;
        }

        $this->fail('bin/seed.php failed: ' . implode("\n", $output));
    }
}
