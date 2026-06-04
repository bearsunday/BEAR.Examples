<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\QueryRepository\RepositoryLoggerInterface;
use BEAR\RepositoryModule\Annotation\DonutCache;
use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Resource\App\Cache\ArticlePreview;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

use function json_decode;
use function str_contains;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * Donut cache pipeline showcase for list resources.
 *
 * Articles/Categories are annotated with #[CacheableResponse] (class level).
 * Article and Category writes carry #[Purge(uri: 'app://self/{collection}')]
 * so the matching list cache is invalidated after create/update/delete. This
 * test asserts those operations show up in the repository log, which is the
 * educational signal that the wiring is in place.
 *
 * Note: #[Purge(uri)] invalidates the canonical URI only. Query-string
 * variants (e.g. app://self/articles?categoryId=3) keep their own cache
 * entries and are not purged by these annotations — see scope.md D1.
 */
final class CacheTest extends TestCase
{
    private ResourceInterface $resource;
    private RepositoryLoggerInterface $logger;

    protected function setUp(): void
    {
        $injector = Injector::getInstance('test-hal-api-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
        $this->logger = $injector->getInstance(RepositoryLoggerInterface::class);
        $this->logger->reset();
    }

    public function testArticlesGetTriggersDonutPipeline(): void
    {
        $ro = $this->resource->get('app://self/articles');
        $this->assertSame(200, $ro->code);

        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"try-donut-view"', $log);
        $this->assertStringContainsString('"op":"put-donut"', $log);
        $this->assertStringContainsString('"op":"save-etag"', $log);
    }

    public function testArticlePreviewUsesExplicitDonutCache(): void
    {
        $reflection = new ReflectionClass(ArticlePreview::class);
        $this->assertNotSame([], $reflection->getAttributes(DonutCache::class));

        $ro = $this->resource->get('app://self/cache/articlepreview', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $body = json_decode((string) $ro, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($body);
        $this->assertSame(1, $body['id']);
        $this->assertSame('DonutCache explicit HAL preview', $body['cachePattern']);
        $this->assertArrayNotHasKey('_embedded', $body);

        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"try-donut-view"', $log);
        $this->assertStringContainsString('"op":"put-donut"', $log);
        $this->assertStringContainsString('"uri":"app://self/cache/articlepreview?id=1"', $log);
    }

    public function testArticlePostPurgesArticlesList(): void
    {
        $this->resource->get('app://self/articles');
        $this->logger->reset();

        $post = $this->resource->post('app://self/article', [
            'slug' => 'cache-purge-' . uniqid(),
            'title' => 'Cache purge demo',
            'body' => 'body',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $this->assertSame(201, $post->code);

        $this->assertPurgedArticlesList();
    }

    public function testArticlePutPurgesArticlesList(): void
    {
        $id = $this->createArticle();
        $this->logger->reset();

        $put = $this->resource->put('app://self/article', [
            'id' => $id,
            'title' => 'Updated',
            'body' => 'updated body',
            'status' => 'published',
        ]);
        $this->assertSame(200, $put->code);

        $this->assertPurgedArticlesList();
    }

    public function testArticleDeletePurgesArticlesList(): void
    {
        $id = $this->createArticle();
        $this->logger->reset();

        $delete = $this->resource->delete('app://self/article', ['id' => $id]);
        $this->assertSame(204, $delete->code);

        $this->assertPurgedArticlesList();
    }

    public function testCategoriesGetTriggersDonutPipeline(): void
    {
        $ro = $this->resource->get('app://self/categories');
        $this->assertSame(200, $ro->code);

        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"put-donut"', $log);
        $this->assertStringContainsString('"uri":"app://self/categories"', $log);
    }

    public function testCategoryWritesPurgeCategoriesList(): void
    {
        $slug = 'cache-purge-cat-' . uniqid();

        $this->logger->reset();
        $post = $this->resource->post('app://self/category', [
            'slug' => $slug,
            'name' => 'Cache purge cat',
        ]);
        $this->assertSame(201, $post->code);
        $this->assertPurgedCategoriesList();

        $id = $post->body['id'];
        $this->logger->reset();
        $put = $this->resource->put('app://self/category', [
            'id' => $id,
            'name' => 'Renamed',
        ]);
        $this->assertSame(200, $put->code);
        $this->assertPurgedCategoriesList();

        $this->logger->reset();
        $delete = $this->resource->delete('app://self/category', ['id' => $id]);
        $this->assertSame(204, $delete->code);
        $this->assertPurgedCategoriesList();
    }

    private function createArticle(): int
    {
        $post = $this->resource->post('app://self/article', [
            'slug' => 'cache-purge-' . uniqid(),
            'title' => 'Seed for purge test',
            'body' => 'body',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $this->assertSame(201, $post->code);

        return $post->body['id'];
    }

    private function assertPurgedArticlesList(): void
    {
        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"purge-query-repository"', $log);
        $this->assertTrue(
            str_contains($log, '"uri":"app://self/articles"'),
            'expected the articles list URI to appear in the purge log: ' . $log,
        );
    }

    private function assertPurgedCategoriesList(): void
    {
        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"purge-query-repository"', $log);
        $this->assertTrue(
            str_contains($log, '"uri":"app://self/categories"'),
            'expected the categories list URI to appear in the purge log: ' . $log,
        );
    }
}
