<?php

declare(strict_types=1);

namespace MyVendor\Cms\Smoke;

use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

use function array_diff;
use function array_keys;
use function array_unique;
use function basename;
use function copy;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_exists;
use function file_get_contents;
use function glob;
use function implode;
use function is_string;
use function preg_match_all;
use function scandir;
use function sort;
use function sprintf;
use function str_ends_with;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

use const PHP_BINARY;

/**
 * Layer-1 smoke test for var/db/sql/*.sql.
 *
 * For every SQL file we look up bind values in tests/params/sql_params.php
 * and assert PDO::prepare() + execute() succeeds against a SQLite database
 * that has been migrated and seeded from var/fake/*.json. Each test runs
 * inside a transaction that is rolled back, so write SQLs leave no trace
 * and the seeded baseline is reused across cases.
 *
 * Pure execution check: we deliberately do NOT assert row counts or
 * returned values. Output shape is covered by JSON Schema in the Resource
 * tests; performance/EXPLAIN is the job of Koriym.SqlQuality.
 */
final class SqlSmokeTest extends TestCase
{
    private const string SQL_DIR = __DIR__ . '/../../var/db/sql';
    private const string PARAMS_FILE = __DIR__ . '/../params/sql_params.php';

    private static string $dbPath = '';
    private static string $templatePath = '';

    private PDO $pdo;

    public static function setUpBeforeClass(): void
    {
        // Build a seeded SQLite template once for the whole class. Per-test
        // we copy the file (cheap on tmpfs) so each test starts from the
        // identical baseline.
        $tmp = (string) tempnam(sys_get_temp_dir(), 'cms_sql_smoke_');
        unlink($tmp);
        self::$templatePath = $tmp . '.db';

        $projectRoot = dirname(__DIR__, 2);
        $dsn = 'sqlite:' . self::$templatePath;
        $env = sprintf(
            'DB_DSN=%s DB_USER= DB_PASSWORD=',
            escapeshellarg($dsn),
        );

        exec(
            sprintf(
                'cd %s && %s %s vendor/bin/doctrine-migrations migrate --no-interaction 2>&1',
                escapeshellarg($projectRoot),
                $env,
                escapeshellarg(PHP_BINARY),
            ),
            $migrateOut,
            $migrateCode,
        );
        if ($migrateCode !== 0) {
            self::fail('doctrine-migrations migrate failed: ' . implode("\n", $migrateOut));
        }

        exec(
            sprintf(
                'cd %s && %s %s bin/seed.php 2>&1',
                escapeshellarg($projectRoot),
                $env,
                escapeshellarg(PHP_BINARY),
            ),
            $seedOut,
            $seedCode,
        );
        if ($seedCode === 0) {
            return;
        }

        self::fail('bin/seed.php failed: ' . implode("\n", $seedOut));
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$templatePath === '' || ! file_exists(self::$templatePath)) {
            return;
        }

        @unlink(self::$templatePath);
    }

    protected function setUp(): void
    {
        self::$dbPath = self::$templatePath . '.' . uniqid('case', true);
        $this->assertTrue(
            copy(self::$templatePath, self::$dbPath),
            sprintf('Failed to copy seeded SQLite template DB to %s', self::$dbPath),
        );

        $this->pdo = new PDO('sqlite:' . self::$dbPath, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->assertNotFalse(
            $this->pdo->exec('PRAGMA foreign_keys = ON'),
            'Failed to enable SQLite foreign key constraints',
        );
    }

    protected function tearDown(): void
    {
        unset($this->pdo);
        if (self::$dbPath === '' || ! file_exists(self::$dbPath)) {
            return;
        }

        @unlink(self::$dbPath);
    }

    public function testEverySqlFileHasParams(): void
    {
        $files = [];
        foreach ((array) scandir(self::SQL_DIR) as $entry) {
            if (! is_string($entry) || ! str_ends_with($entry, '.sql')) {
                continue;
            }

            $files[] = $entry;
        }

        sort($files);
        $params = require self::PARAMS_FILE;
        $missing = array_diff($files, array_keys($params));
        $extra = array_diff(array_keys($params), $files);

        $this->assertSame([], $missing, 'SQL files without params entry: ' . implode(', ', $missing));
        $this->assertSame([], $extra, 'Params entries without matching SQL file: ' . implode(', ', $extra));
    }

    public function testEverySqlFileParamsMatchPlaceholders(): void
    {
        $params = require self::PARAMS_FILE;
        $files = (array) glob(self::SQL_DIR . '/*.sql');
        sort($files);

        foreach ($files as $path) {
            $name = basename((string) $path);
            $actual = array_keys($params[$name] ?? []);
            sort($actual);

            $this->assertSame(
                self::placeholderNames((string) file_get_contents((string) $path)),
                $actual,
                "{$name} params do not match SQL placeholders",
            );
        }
    }

    /** @param array<string, mixed> $params */
    #[DataProvider('sqlProvider')]
    public function testSqlExecutes(string $sqlFile, array $params): void
    {
        $sql = (string) file_get_contents(self::SQL_DIR . '/' . $sqlFile);

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare($sql);
            $this->assertNotFalse($stmt, "Failed to prepare {$sqlFile}");
            $ok = $stmt->execute($params);
            $this->assertTrue($ok, "Failed to execute {$sqlFile}");
            if ($stmt->columnCount() > 0) {
                // Close SELECT cursors before rolling back the per-test transaction.
                $stmt->closeCursor();
            }
        } finally {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
        }
    }

    /** @return iterable<string, array{0: string, 1: array<string, mixed>}> */
    public static function sqlProvider(): iterable
    {
        $params = require self::PARAMS_FILE;
        $files = (array) glob(self::SQL_DIR . '/*.sql');
        sort($files);

        foreach ($files as $path) {
            $name = basename((string) $path);

            yield $name => [$name, $params[$name] ?? []];
        }
    }

    /** @return array<int, string> */
    private static function placeholderNames(string $sql): array
    {
        $matches = [];
        preg_match_all('/(?<!:):([A-Za-z_][A-Za-z0-9_]*)/', $sql, $matches);

        $names = array_unique($matches[1]);
        sort($names);

        return $names;
    }
}
