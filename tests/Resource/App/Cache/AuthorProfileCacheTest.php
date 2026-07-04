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
use function json_decode;
use function substr_count;

use const JSON_THROW_ON_ERROR;

/**
 * `Cache\AuthorProfile` composes `Cache\Author` via `#[Embed]` (HAL renders it
 * into `_embedded.author`) and gets cross-resource invalidation for free:
 * `QueryRepository::setCacheDependency` merges the child's Surrogate-Key into
 * the parent automatically. The class therefore contains zero manual cache
 * primitives — composition is declared by `#[Embed]` alone.
 */
final class AuthorProfileCacheTest extends TestCase
{
    private ResourceInterface $resource;
    private HttpCacheInterface $httpCache;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->httpCache = $injector->getInstance(HttpCacheInterface::class);
    }

    public function testEmbedRendersAndSurrogateKeyDeclaresChildDependency(): void
    {
        $ro = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $body = json_decode((string) $ro, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $body['authorId']);
        $this->assertSame(1, $body['_embedded']['author']['id']);
        $this->assertSame($body['authorId'], $body['_embedded']['author']['id']);

        $this->assertArrayHasKey(Header::ETAG, $ro->headers);
        $this->assertArrayHasKey(Header::SURROGATE_KEY, $ro->headers);
        // QueryRepository merges the child's Surrogate-Key into the parent
        // automatically, so PUT app://self/cache/author?id=1 cascades into
        // this response's invalidation set with no manual cache code.
        $this->assertStringContainsString('_cache_author_id=1', $ro->headers[Header::SURROGATE_KEY]);

        $etag = $ro->headers[Header::ETAG];
        $this->assertTrue($this->httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $etag]));

        $cached = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $this->assertSame($etag, $cached->headers[Header::ETAG]);
        $this->assertSame((string) $ro, (string) $cached);
    }

    public function testUpdatingEmbeddedAuthorInvalidatesParentEtag(): void
    {
        $first = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $firstView = (string) $first;
        $oldEtag = $first->headers[Header::ETAG];

        $put = $this->resource->put('app://self/cache/author', [
            'id' => 1,
            'name' => 'Cache Demo Author',
            'email' => 'cache-demo-author@example.com',
            'bio' => 'Updated through the cache showcase resource.',
        ]);

        $this->assertSame(200, $put->code);
        $this->assertFalse($this->httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]));

        $second = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $secondView = (string) $second;

        $this->assertNotSame($oldEtag, $second->headers[Header::ETAG]);
        $this->assertNotSame($firstView, $secondView);
        $this->assertStringContainsString('Cache Demo Author', $secondView);
    }

    public function testMissingAuthorReturns404AndDropsEmbed(): void
    {
        $ro = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 9999]);
        $body = json_decode((string) $ro, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);

        $this->assertSame(404, $ro->code);
        $this->assertSame(9999, $body['id']);
        $this->assertSame('Author not found', $body['message']);
        // Replacing $this->body in onGet drops the Embed Request, so the 404
        // payload does not carry a half-resolved _embedded.author.
        $this->assertArrayNotHasKey('_embedded', $body);
        // CacheInterceptor only stores responses with code 200; anything else
        // takes the purge branch, so the 404 never gets stored as a fresh ETag.
        $this->assertArrayNotHasKey(Header::ETAG, $ro->headers);
    }

    public function testEmbeddedAuthorShapeIsStableWhenChildResourceIsAlreadyCached(): void
    {
        $cold = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $coldBody = json_decode((string) $cold, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($coldBody);

        $resource = $this->freshResource();
        // Warm the child in the same injector that renders the parent.
        $resource->get('app://self/cache/author', ['id' => 1]);
        $warm = $resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $warmBody = json_decode((string) $warm, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($warmBody);

        $this->assertSame($coldBody['_embedded']['author'], $warmBody['_embedded']['author']);
        $this->assertSame($cold->headers[Header::ETAG], $warm->headers[Header::ETAG]);
        // Pin the auto-merge under the warm-child path: even when the
        // embedded resource is served from cache, the parent's Surrogate-Key
        // must still carry the child URI tag so cross-resource invalidation
        // keeps working.
        $this->assertArrayHasKey(Header::SURROGATE_KEY, $warm->headers);
        $this->assertStringContainsString('_cache_author_id=1', $warm->headers[Header::SURROGATE_KEY]);
    }

    /**
     * The parent class must contain zero manual cache primitives: dependency
     * resolution is handled by `QueryRepository::setCacheDependency` walking
     * the body. Refactors that reach for `fromAssoc`, assign to Surrogate-Key,
     * or drop down to manual `$this->resource->get(...)` should fail this test.
     */
    public function testSourceHasNoManualCacheCode(): void
    {
        $path = (new ReflectionClass(AuthorProfile::class))->getFileName();
        $this->assertIsString($path);
        $src = file_get_contents($path);
        $this->assertIsString($src);

        $this->assertSame(
            0,
            substr_count($src, 'fromAssoc('),
            'Cache\\AuthorProfile must contain no fromAssoc() calls.',
        );
        $this->assertSame(
            0,
            substr_count($src, 'Header::SURROGATE_KEY'),
            'Cache\\AuthorProfile must contain no Header::SURROGATE_KEY references.',
        );
        // No manual resource fetching — composition must use #[Embed].
        $this->assertStringNotContainsString('ResourceInterface', $src);
        $this->assertStringNotContainsString('$this->resource->get', $src);
        $this->assertStringNotContainsString('json_decode', $src);
    }

    private function freshResource(): ResourceInterface
    {
        $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());

        return $injector->getInstance(ResourceInterface::class);
    }
}
