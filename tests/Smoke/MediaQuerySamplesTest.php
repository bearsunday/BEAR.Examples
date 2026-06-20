<?php

declare(strict_types=1);

namespace BEAR\Examples\Smoke;

use BEAR\Examples\Entity\Article;
use BEAR\Examples\Injector;
use BEAR\Examples\Query\ArticleCommandInterface;
use BEAR\Examples\Query\ArticleQueryInterface;
use BEAR\Examples\Query\ArticleSelectionQueryInterface;
use BEAR\Examples\Query\Samples\ArticleAffectedRowsCommandInterface;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\PagesInterface;

use function iterator_to_array;
use function uniqid;

final class MediaQuerySamplesTest extends TestCase
{
    public function testPagerSampleReturnsPagesInterface(): void
    {
        $query = Injector::getInstance('test-hal-api-app')->getInstance(ArticleQueryInterface::class);

        $pages = $query->list(status: 'published', perPage: 2);
        $page = $pages[1];

        $this->assertInstanceOf(PagesInterface::class, $pages);
        $this->assertInstanceOf(Page::class, $page);
        $this->assertCount(2, $page->data);
        $this->assertIsArray($page->data[0]);
        $this->assertArrayHasKey('published_at', $page->data[0]);
    }

    public function testSelectResultClassWrapsHydratedRows(): void
    {
        $query = Injector::getInstance('test-hal-api-app')->getInstance(ArticleSelectionQueryInterface::class);

        $articles = $query->list('published');

        $this->assertGreaterThan(0, $articles->count());
        $this->assertNotSame([], $articles->titles());
        $this->assertSame($articles->count(), $articles->published()->count());

        $first = $articles->first();
        $this->assertInstanceOf(Article::class, $first);
        $this->assertTrue($first->isPublished());
        $this->assertContainsOnlyInstancesOf(Article::class, iterator_to_array($articles));
    }

    public function testAffectedRowsSampleReturnsDmlMetadata(): void
    {
        $injector = Injector::getInstance('test-hal-api-app');
        $article = $injector->getInstance(ArticleQueryInterface::class);
        $canonicalCommand = $injector->getInstance(ArticleCommandInterface::class);
        $resultCommand = $injector->getInstance(ArticleAffectedRowsCommandInterface::class);
        $slug = 'affected-rows-sample-' . uniqid();

        $canonicalCommand->add($slug, 'Draft', 'Body', null, 'draft', null, 1, 1);
        $created = $article->bySlug($slug);
        $this->assertInstanceOf(Article::class, $created);

        $updated = $resultCommand->update(
            $created->id,
            'Updated',
            'Body',
            null,
            'published',
            '2026-01-01 00:00:00',
        );
        $deleted = $resultCommand->delete($created->id);
        $missing = $resultCommand->delete($created->id);

        $this->assertSame(1, $updated->count);
        $this->assertTrue($updated->isAffected());
        $this->assertSame(1, $deleted->count);
        $this->assertTrue($deleted->isAffected());
        $this->assertSame(0, $missing->count);
        $this->assertFalse($missing->isAffected());
    }
}
