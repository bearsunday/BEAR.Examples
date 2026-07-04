<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Cache;

use BEAR\Kata\Injector;
use BEAR\Kata\Module\CacheShowcaseModule;
use BEAR\QueryRepository\Header;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function file_get_contents;

/**
 * Cache showcase — `Cache\Author` is the canonical user-zero-code leaf.
 *
 * Beyond the runtime ETag and PUT-invalidation checks, the reflection test
 * pins the source-code invariant: zero cache primitives. This is the
 * executable enforcement of `docs/conventions.md` § "Default — automatic
 * tag-based invalidation".
 */
final class AuthorCacheTest extends TestCase
{
    private ResourceInterface $resource;
    private HttpCacheInterface $httpCache;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->httpCache = $injector->getInstance(HttpCacheInterface::class);
    }

    public function testGetSetsEtag(): void
    {
        $ro = $this->resource->get('app://self/cache/author', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey(Header::ETAG, $ro->headers);
        $this->assertArrayHasKey(Header::LAST_MODIFIED, $ro->headers);
    }

    public function testRepeatedGetIsCacheHit(): void
    {
        $first = $this->resource->get('app://self/cache/author', ['id' => 1]);
        $second = $this->resource->get('app://self/cache/author', ['id' => 1]);

        $this->assertSame($first->headers[Header::ETAG], $second->headers[Header::ETAG]);
        $this->assertSame((string) $first, (string) $second);
        $this->assertTrue($this->httpCache->isNotModified([
            Header::HTTP_IF_NONE_MATCH => $first->headers[Header::ETAG],
        ]));
    }

    public function testPutInvalidatesEtag(): void
    {
        $first = $this->resource->get('app://self/cache/author', ['id' => 1]);
        $oldEtag = $first->headers[Header::ETAG];

        $put = $this->resource->put('app://self/cache/author', [
            'id' => 1,
            'name' => 'Edited Author',
            'email' => 'edited.author@example.com',
            'bio' => 'Edited via Cache\\Author PUT.',
        ]);
        $this->assertSame(200, $put->code);

        $this->assertFalse($this->httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]));

        $second = $this->resource->get('app://self/cache/author', ['id' => 1]);
        $this->assertNotSame($oldEtag, $second->headers[Header::ETAG]);
        $this->assertStringContainsString('Edited Author', (string) $second);
    }

    /**
     * The leaf must contain no manual cache primitives. The framework
     * auto-resolves everything via `#[Cacheable]` + `CacheInterceptor` (which
     * stores the resource tagged with its self URI) + `CommandInterceptor`
     * with `RefreshSameCommand` (which purges that URI tag on write).
     */
    public function testSourceContainsNoCachePrimitives(): void
    {
        $path = (new ReflectionClass(Author::class))->getFileName();
        $this->assertIsString($path);
        $src = file_get_contents($path);
        $this->assertIsString($src);

        foreach (
            [
                'Header::SURROGATE_KEY',
                'UriTagInterface',
                'DonutRepositoryInterface',
                'invalidateTags',
                'fromAssoc',
            ] as $needle
        ) {
            $this->assertStringNotContainsString(
                $needle,
                $src,
                "Cache\\Author must not contain manual cache primitive '{$needle}'.",
            );
        }
    }
}
