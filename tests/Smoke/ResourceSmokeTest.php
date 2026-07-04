<?php

declare(strict_types=1);

namespace BEAR\Kata\Smoke;

use BEAR\Kata\AbstractAppTestCase;
use BEAR\Kata\Injector;
use BEAR\Kata\Module\CacheShowcaseModule;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\ResourceInterface;
use JsonSchema\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;

use function array_diff;
use function array_is_list;
use function array_keys;
use function array_map;
use function class_exists;
use function explode;
use function file_get_contents;
use function implode;
use function is_array;
use function is_file;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function sort;
use function sprintf;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strtolower;
use function substr;

use const DIRECTORY_SEPARATOR;
use const JSON_THROW_ON_ERROR;

/**
 * Layer-3 smoke test for App resources.
 *
 * The test walks canonical App resources, invokes every GET-able resource with
 * fake fixture arguments, and validates the rendered representation against the
 * same JSON Schema declared on `onGet()`. Top-level array schemas validate the
 * resource body before HAL normalises numeric keys into an object-shaped
 * document. `Variations/` are intentionally excluded: they are comparison
 * examples with their own focused tests, not the canonical App API surface.
 */
final class ResourceSmokeTest extends AbstractAppTestCase
{
    private const string RESOURCE_DIR = __DIR__ . '/../../src/Resource/App';
    private const string ARGS_FILE = __DIR__ . '/../params/resource_args.php';
    private const string SCHEMA_DIR = __DIR__ . '/../../var/json_schema';

    private ResourceInterface $cacheResource;

    protected function setUp(): void
    {
        parent::setUp();

        $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
        $this->cacheResource = $injector->getInstance(ResourceInterface::class);
    }

    public function testEveryGetResourceHasArgs(): void
    {
        $resources = self::getResources();
        $args = self::resourceArgs();

        $missing = array_diff(array_keys($resources), array_keys($args));
        $extra = array_diff(array_keys($args), array_keys($resources));

        $this->assertSame([], $missing, 'GET resources without args entry: ' . implode(', ', $missing));
        $this->assertSame([], $extra, 'Args entries without matching GET resource: ' . implode(', ', $extra));
    }

    /** @param array<string, mixed> $args */
    #[DataProvider('resourceProvider')]
    public function testGetResourceReturnsOkAndMatchesSchema(string $uri, array $args, string $schemaName): void
    {
        $resource = str_starts_with($uri, 'app://self/cache/') ? $this->cacheResource : $this->resource;
        $ro = $resource->get($uri, $args);

        $this->assertSame(200, $ro->code, $uri . ' did not return 200');

        $schema = self::schema($schemaName);
        $view = $schema->type === 'array'
            ? json_decode(json_encode($ro->body, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR)
            : json_decode((string) $ro, false, 512, JSON_THROW_ON_ERROR);
        $validator = new Validator();
        $validator->validate($view, $schema);

        $this->assertTrue(
            $validator->isValid(),
            sprintf("%s did not match %s:\n%s", $uri, $schemaName, self::formatErrors($validator)),
        );
    }

    /** @return iterable<string, array{0: string, 1: array<string, mixed>, 2: string}> */
    public static function resourceProvider(): iterable
    {
        $args = self::resourceArgs();
        foreach (self::getResources() as $uri => $schemaName) {
            $getArgs = $args[$uri]['get'] ?? null;
            if (! is_array($getArgs)) {
                throw new RuntimeException(sprintf('%s has no get args entry', $uri));
            }

            yield $uri => [$uri, $getArgs, $schemaName];
        }
    }

    /** @return array<string, string> uri => schema filename */
    private static function getResources(): array
    {
        $resources = [];
        foreach (self::resourceFiles() as $path) {
            $class = self::className($path);
            $reflection = new ReflectionClass($class);
            if (! $reflection->hasMethod('onGet')) {
                continue;
            }

            $method = $reflection->getMethod('onGet');
            $attributes = $method->getAttributes(JsonSchema::class);
            if ($attributes === []) {
                throw new RuntimeException(sprintf('%s::onGet() has no JsonSchema attribute', $class));
            }

            $schema = $attributes[0]->newInstance()->schema;
            if ($schema === '') {
                throw new RuntimeException(sprintf('%s::onGet() has an empty response schema', $class));
            }

            $resources[self::uri($path)] = $schema;
        }

        return $resources;
    }

    /** @return list<string> */
    private static function resourceFiles(): array
    {
        $paths = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::RESOURCE_DIR)) as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            $path = $file->getPathname();
            if (! is_file($path) || ! str_ends_with($path, '.php')) {
                continue;
            }

            if (str_starts_with($path, self::RESOURCE_DIR . DIRECTORY_SEPARATOR . 'Variations' . DIRECTORY_SEPARATOR)) {
                continue;
            }

            $paths[] = $path;
        }

        sort($paths);

        return $paths;
    }

    /** @return class-string */
    private static function className(string $path): string
    {
        $relative = substr($path, strlen(self::RESOURCE_DIR) + 1, -4);
        $class = 'BEAR\\Kata\\Resource\\App\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
        if (! class_exists($class)) {
            throw new RuntimeException('Resource class does not exist: ' . $class);
        }

        return $class;
    }

    private static function uri(string $path): string
    {
        $relative = substr($path, strlen(self::RESOURCE_DIR) + 1, -4);
        $parts = array_map(strtolower(...), explode(DIRECTORY_SEPARATOR, $relative));

        return 'app://self/' . implode('/', $parts);
    }

    /** @return array<string, array{get: array<string, mixed>}> */
    private static function resourceArgs(): array
    {
        $args = require self::ARGS_FILE;
        if (! is_array($args)) {
            throw new RuntimeException(self::ARGS_FILE . ' must return an array');
        }

        return $args;
    }

    private static function schema(string $schemaName): object
    {
        $schema = json_decode(
            json_encode(self::schemaArray($schemaName), JSON_THROW_ON_ERROR),
            false,
            512,
            JSON_THROW_ON_ERROR,
        );
        if (! is_object($schema)) {
            throw new RuntimeException(sprintf('%s did not decode to an object schema', $schemaName));
        }

        return $schema;
    }

    /** @return array<string, mixed> */
    private static function schemaArray(string $schemaName): array
    {
        $schemaFile = self::SCHEMA_DIR . '/' . $schemaName;
        if (! is_file($schemaFile)) {
            throw new RuntimeException(sprintf('Schema file not found: %s', $schemaFile));
        }

        $json = file_get_contents($schemaFile);
        if ($json === false) {
            throw new RuntimeException(sprintf('Schema file could not be read: %s', $schemaFile));
        }

        $schema = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (! is_array($schema)) {
            throw new RuntimeException(sprintf('%s did not decode to an array schema', $schemaFile));
        }

        return self::resolveRefs($schema);
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>
     */
    private static function resolveRefs(array $node): array
    {
        $ref = $node['$ref'] ?? null;
        if (is_string($ref)) {
            if (str_ends_with($ref, '.json')) {
                return self::schemaArray($ref);
            }

            throw new RuntimeException(sprintf('Unsupported schema $ref pattern: %s', $ref));
        }

        foreach ($node as $key => $value) {
            if (! is_array($value)) {
                continue;
            }

            $node[$key] = self::resolveArrayValue($value);
        }

        return $node;
    }

    /**
     * @param array<mixed> $value
     *
     * @return array<mixed>
     */
    private static function resolveArrayValue(array $value): array
    {
        if (array_is_list($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = is_array($item) ? self::resolveArrayValue($item) : $item;
            }

            return $value;
        }

        $assoc = $value;
        /** @var array<string, mixed> $assoc */

        return self::resolveRefs($assoc);
    }

    private static function formatErrors(Validator $validator): string
    {
        $messages = [];
        foreach ($validator->getErrors() as $error) {
            $messages[] = sprintf('[%s] %s', $error['property'], $error['message']);
        }

        return implode("\n", $messages);
    }
}
