<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Cache;

use BEAR\QueryRepository\Header;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use BEAR\Kata\Injector;
use BEAR\Kata\Module\CacheShowcaseModule;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function array_map;
use function file_get_contents;
use function substr_count;

/**
 * Executable equivalent of `docs/conventions.md` § "Exception —
 * UriTagInterface::fromAssoc for body-variable dependencies". The Surrogate-Key
 * dependency set is body-derived and variable-length, so `#[Embed]` cannot
 * express it statically — exactly one line of manual cache code is allowed,
 * pinned by `testSourceHasExactlyOneFromAssocCall`.
 *
 * Invalidation surface (also documented on `Cache\ArticleTags`):
 *   - `PUT app://self/cache/tag?id={tagId}` cascades through Surrogate-Key.
 *   - `PUT app://self/cache/articletags?articleId={id}` is the showcase's own
 *     write entry point and self-purges via `RefreshSameCommand`.
 * Writes to the main `app://self/article` resource that change `tagIds` are
 * intentionally NOT wired into this cache — see the class docblock for the
 * scope rationale.
 */
final class ArticleTagsCacheTest extends TestCase
{
    private ResourceInterface $resource;
    private HttpCacheInterface $httpCache;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->httpCache = $injector->getInstance(HttpCacheInterface::class);
    }

    public function testFromAssocBuildsTagSurrogateKey(): void
    {
        $ro = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);

        $this->assertSame(200, $ro->code);
        $this->assertArrayHasKey(Header::ETAG, $ro->headers);
        $this->assertArrayHasKey(Header::SURROGATE_KEY, $ro->headers);

        // Fixture (var/fake/articleTag.json): article 3 has tags 14, 18, 20, 47.
        // The whole Surrogate-Key value is the fromAssoc-built dependency set;
        // each tag URI must appear as a token.
        foreach (['_cache_tag_id=14', '_cache_tag_id=18', '_cache_tag_id=20', '_cache_tag_id=47'] as $tagToken) {
            $this->assertStringContainsString(
                $tagToken,
                $ro->headers[Header::SURROGATE_KEY],
                "Surrogate-Key missing dependency token '{$tagToken}'.",
            );
        }
    }

    public function testEmptyTagListProducesNoSurrogateKey(): void
    {
        $ro = $this->resource->get('app://self/cache/articletags', ['articleId' => 9999]);

        $this->assertSame(200, $ro->code);
        // `UriTagInterface::fromAssoc([])` returns an empty string, and
        // Symfony's tag-aware cache adapter rejects empty tags. The resource
        // guards against that by leaving the header unset; the framework's
        // own URI-tag invalidation still covers the empty-set case.
        $this->assertArrayNotHasKey(Header::SURROGATE_KEY, $ro->headers);
    }

    public function testRepeatedGetIsCacheHit(): void
    {
        $first = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $second = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);

        $this->assertSame($first->headers[Header::ETAG], $second->headers[Header::ETAG]);
        $this->assertSame((string) $first, (string) $second);
        $this->assertTrue($this->httpCache->isNotModified([
            Header::HTTP_IF_NONE_MATCH => $first->headers[Header::ETAG],
        ]));
    }

    public function testPuttingOneOfTheDependencyTagsInvalidates(): void
    {
        $first = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $oldEtag = $first->headers[Header::ETAG];

        // Tag 14 is in article 3's dependency set.
        $put = $this->resource->put('app://self/cache/tag', [
            'id' => 14,
            'slug' => 'edited-tag-14',
            'name' => 'Edited Tag 14',
        ]);
        $this->assertSame(200, $put->code);

        $this->assertFalse($this->httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]));

        $second = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $this->assertNotSame($oldEtag, $second->headers[Header::ETAG]);
        $this->assertStringContainsString('Edited Tag 14', (string) $second);
    }

    public function testPuttingUnrelatedTagDoesNotInvalidate(): void
    {
        $first = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $oldEtag = $first->headers[Header::ETAG];

        // Tag 5 is NOT in article 3's dependency set.
        $put = $this->resource->put('app://self/cache/tag', [
            'id' => 5,
            'slug' => 'unrelated-tag-5',
            'name' => 'Unrelated Tag 5',
        ]);
        $this->assertSame(200, $put->code);

        $this->assertTrue($this->httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]));

        $second = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $this->assertSame($oldEtag, $second->headers[Header::ETAG]);
    }

    public function testPutInvalidatesSelfEtagAndRebuildsDependencySet(): void
    {
        $first = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $oldEtag = $first->headers[Header::ETAG];
        $this->assertStringContainsString('_cache_tag_id=14', $first->headers[Header::SURROGATE_KEY]);

        // Replace article 3's tag set: drop 14/18/20/47, keep none of them.
        $put = $this->resource->put('app://self/cache/articletags', [
            'articleId' => 3,
            'tagIds' => [5, 7],
        ]);
        $this->assertSame(200, $put->code);

        // RefreshSameCommand on the self URI tag invalidates the stored entry.
        $this->assertFalse($this->httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]));

        $second = $this->resource->get('app://self/cache/articletags', ['articleId' => 3]);
        $this->assertNotSame($oldEtag, $second->headers[Header::ETAG]);
        $this->assertSame(2, $second->body['count']);
        $this->assertSame([5, 7], array_map(static fn ($i) => $i['id'], $second->body['items']));
        // New dependency set: tag 14 must be gone, tag 5 must be in.
        $this->assertStringNotContainsString('_cache_tag_id=14', $second->headers[Header::SURROGATE_KEY]);
        $this->assertStringContainsString('_cache_tag_id=5', $second->headers[Header::SURROGATE_KEY]);
    }

    /**
     * The exception class is allowed exactly one manual cache primitive: the
     * fromAssoc assignment to Surrogate-Key. Refactors that add another
     * fromAssoc call or another Surrogate-Key write should fail this test.
     */
    public function testSourceHasExactlyOneFromAssocCall(): void
    {
        $path = (new ReflectionClass(ArticleTags::class))->getFileName();
        $this->assertIsString($path);
        $src = file_get_contents($path);
        $this->assertIsString($src);

        $this->assertSame(
            1,
            substr_count($src, 'fromAssoc('),
            'Cache\\ArticleTags must contain exactly one fromAssoc() call.',
        );
        $this->assertSame(
            1,
            substr_count($src, 'Header::SURROGATE_KEY'),
            'Cache\\ArticleTags must contain exactly one Header::SURROGATE_KEY reference.',
        );
    }
}
