<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\QueryRepository\Header;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Module\CacheShowcaseModule;
use PHPUnit\Framework\TestCase;

use function json_decode;

use const JSON_THROW_ON_ERROR;

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

    public function testCacheableResponseStoresUriDependencyAndEtag(): void
    {
        $ro = $this->resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $body = json_decode((string) $ro, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $body['authorId']);
        $this->assertSame(1, $body['_embedded']['author']['id']);
        $this->assertArrayNotHasKey('_links', $body['_embedded']['author']);
        $this->assertArrayHasKey(Header::ETAG, $ro->headers);
        $this->assertArrayHasKey(Header::SURROGATE_KEY, $ro->headers);
        $this->assertStringContainsString('_cache_authorprofile_authorId=1', $ro->headers[Header::SURROGATE_KEY]);
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

        $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
        $resource = $injector->getInstance(ResourceInterface::class);
        $resource->get('app://self/cache/author', ['id' => 1]);
        $warm = $resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
        $warmBody = json_decode((string) $warm, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($warmBody);

        $this->assertSame($coldBody['_embedded']['author'], $warmBody['_embedded']['author']);
        $this->assertSame($cold->headers[Header::ETAG], $warm->headers[Header::ETAG]);
    }
}
