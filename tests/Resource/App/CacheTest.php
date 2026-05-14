<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\QueryRepository\RepositoryLoggerInterface;
use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use PHPUnit\Framework\TestCase;

use function str_contains;
use function uniqid;

/**
 * Donut cache pipeline showcase for list resources.
 *
 * Articles/Categories are annotated with #[CacheableResponse] (class level).
 * Article writes carry #[Purge(uri: 'app://self/articles')] so the list cache
 * is invalidated after create/update/delete. This test asserts those
 * operations show up in the repository log, which is the educational signal
 * that wiring is in place.
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

    public function testArticlePostPurgesArticlesList(): void
    {
        // Warm the list cache.
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

        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"purge-query-repository"', $log);
        $this->assertTrue(
            str_contains($log, '"uri":"app://self/articles"'),
            'expected the articles list URI to appear in the purge log: ' . $log,
        );
    }

    public function testCategoriesGetTriggersDonutPipeline(): void
    {
        $ro = $this->resource->get('app://self/categories');
        $this->assertSame(200, $ro->code);

        $log = (string) $this->logger;
        $this->assertStringContainsString('"op":"put-donut"', $log);
        $this->assertStringContainsString('"uri":"app://self/categories"', $log);
    }
}
