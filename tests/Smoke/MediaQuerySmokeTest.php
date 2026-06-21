<?php

declare(strict_types=1);

namespace BEAR\Kata\Smoke;

use BEAR\Kata\Injector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

use function array_diff;
use function array_keys;
use function implode;
use function interface_exists;
use function is_file;
use function ksort;
use function sort;
use function str_ends_with;
use function str_replace;
use function strlen;
use function substr;

use const DIRECTORY_SEPARATOR;

/**
 * Layer-2 smoke test for MediaQuery interfaces in src/Query.
 *
 * Every query/command method is invoked through the test DI context, which
 * routes #[DbQuery] calls to FakeSqlQuery instead of a real database. The test
 * deliberately checks callability only; SQL execution itself is covered by
 * SqlSmokeTest and value-level behavior belongs in resource/integration tests.
 */
final class MediaQuerySmokeTest extends TestCase
{
    private const string QUERY_DIR = __DIR__ . '/../../src/Query';
    private const string ARGS_FILE = __DIR__ . '/../params/query_args.php';

    public function testEveryMediaQueryMethodHasArgs(): void
    {
        $methods = self::queryMethods();
        $args = require self::ARGS_FILE;
        $missing = array_diff(array_keys($methods), array_keys($args));
        $extra = array_diff(array_keys($args), array_keys($methods));

        $this->assertSame([], $missing, 'Query methods without args entry: ' . implode(', ', $missing));
        $this->assertSame([], $extra, 'Args entries without matching query method: ' . implode(', ', $extra));
    }

    /** @param list<mixed> $args */
    #[DataProvider('queryMethodProvider')]
    public function testMediaQueryMethodIsCallable(string $interface, string $method, array $args): void
    {
        $injector = Injector::getInstance('test-hal-api-app');
        $instance = $injector->getInstance($interface);

        $instance->{$method}(...$args);

        $this->addToAssertionCount(1);
    }

    /** @return iterable<string, array{0: class-string, 1: string, 2: list<mixed>}> */
    public static function queryMethodProvider(): iterable
    {
        $args = require self::ARGS_FILE;
        $methods = self::queryMethods();

        foreach ($methods as $key => [$interface, $method]) {
            yield $key => [$interface, $method, $args[$key] ?? []];
        }
    }

    /** @return array<string, array{0: class-string, 1: string}> */
    private static function queryMethods(): array
    {
        $methods = [];
        foreach (self::interfaceFiles() as $path) {
            $interface = self::interfaceName($path);
            $reflection = new ReflectionClass($interface);

            foreach ($reflection->getMethods() as $method) {
                $methods[$reflection->getShortName() . '::' . $method->getName()] = [
                    $interface,
                    $method->getName(),
                ];
            }
        }

        ksort($methods);

        return $methods;
    }

    /** @return list<string> */
    private static function interfaceFiles(): array
    {
        $paths = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::QUERY_DIR));
        foreach ($files as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            $path = $file->getPathname();
            if (! is_file($path) || ! str_ends_with($path, 'Interface.php')) {
                continue;
            }

            $paths[] = $path;
        }

        sort($paths);

        return $paths;
    }

    /** @return class-string */
    private static function interfaceName(string $path): string
    {
        $relative = substr($path, strlen(self::QUERY_DIR) + 1, -4);
        $interface = 'BEAR\\Kata\\Query\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        if (! interface_exists($interface)) {
            throw new RuntimeException("Query interface does not exist: {$interface}");
        }

        return $interface;
    }
}
