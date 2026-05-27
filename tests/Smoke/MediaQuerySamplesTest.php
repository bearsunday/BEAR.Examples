<?php

declare(strict_types=1);

namespace MyVendor\Cms\Smoke;

use DateTimeImmutable;
use Generator;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Query\ArticleCommandInterface;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\ArticleSelectionQueryInterface;
use MyVendor\Cms\Query\Samples\ArticleAffectedRowsCommandInterface;
use MyVendor\Cms\Result\ArticleFeedItem;
use PHPUnit\Framework\TestCase;
use Ray\AuraSqlModule\Pagerfanta\Page;
use Ray\MediaQuery\PagesInterface;

use function count;
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
        $this->assertInstanceOf(Generator::class, $articles->published());
        $published = iterator_to_array($articles->published(), false);
        $this->assertSame($articles->count(), count($published));

        $first = $articles->first();
        $this->assertInstanceOf(Article::class, $first);
        $this->assertTrue($first->isPublished());
        $this->assertContainsOnlyInstancesOf(Article::class, iterator_to_array($articles));
    }

    public function testSelectResultClassProjectsFeedItems(): void
    {
        $query = Injector::getInstance('test-hal-api-app')->getInstance(ArticleSelectionQueryInterface::class);

        $articles = $query->list();
        $feed = $articles->feed(new DateTimeImmutable('2026-11-23T16:46:00Z'));

        $this->assertInstanceOf(Generator::class, $feed);
        $items = iterator_to_array($feed, false);
        $this->assertNotSame([], $items);
        $this->assertContainsOnlyInstancesOf(ArticleFeedItem::class, $items);
        $this->assertSame('5 minutes ago', $items[0]->postedAgoLabel);
        $this->assertSame('2026-11-23', $items[0]->publishedAtLabel);
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
