<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\QueryRepository\Header;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Module\CacheShowcaseModule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function file_get_contents;
use function json_decode;
use function substr_count;

use const JSON_THROW_ON_ERROR;

/**
 * Executable equivalent of `docs/conventions.md` § "Cross-resource cache
 * dependency: one `fromAssoc` line". `Cache\AuthorProfile` composes
 * `Cache\Author` via `#[Embed]` (HAL renders it into `_embedded.author`) and
 * declares the cross-resource invalidation contract with exactly one line of
 * cache code: a `fromAssoc` mapping the dependency URI to a Surrogate-Key
 * tag. Symmetric to `Cache\ArticleTags`, just with a single child rather than
 * an N-child body-derived set.
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
        // The one manual line maps the child URI into the parent's
        // Surrogate-Key so PUT app://self/cache/author?id=1 cascades into
        // this response's invalidation set.
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
    }

    /**
     * The parent class is allowed exactly one manual cache primitive: the
     * `fromAssoc` assignment to Surrogate-Key that declares the single-child
     * dependency. Refactors that add a second `fromAssoc`, drop down to
     * manual `$this->resource->get(...)`, or reach for any other cache header
     * should fail this test.
     */
    public function testSourceHasExactlyOneFromAssocCall(): void
    {
        $path = (new ReflectionClass(AuthorProfile::class))->getFileName();
        $this->assertIsString($path);
        $src = file_get_contents($path);
        $this->assertIsString($src);

        $this->assertSame(
            1,
            substr_count($src, 'fromAssoc('),
            'Cache\\AuthorProfile must contain exactly one fromAssoc() call.',
        );
        $this->assertSame(
            1,
            substr_count($src, 'Header::SURROGATE_KEY'),
            'Cache\\AuthorProfile must contain exactly one Header::SURROGATE_KEY reference.',
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
