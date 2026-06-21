<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Crawl;

use BEAR\Kata\AbstractAppTestCase;
use BEAR\Kata\Fake\FakeSqlQuery;
use BEAR\Kata\Injector;
use Ray\MediaQuery\SqlQueryInterface;

use function array_filter;
use function array_values;

final class CrawlDataLoaderTest extends AbstractAppTestCase
{
    private FakeSqlQuery $sql;

    protected function setUp(): void
    {
        parent::setUp();

        $injector = Injector::getInstance('test-hal-api-app');
        $sql = $injector->getInstance(SqlQueryInterface::class);
        $this->assertInstanceOf(FakeSqlQuery::class, $sql);
        $sql->resetQueryLog();
        $this->sql = $sql;
    }

    public function testAuthorTreeCrawlBatchesArticleTagsWithDataLoader(): void
    {
        $ro = $this->resource->crawl('app://self/crawl/author', 'author-tree', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertIsArray($ro->body['articleList']);
        $this->assertNotEmpty($ro->body['articleList']);

        foreach ($ro->body['articleList'] as $article) {
            $this->assertIsArray($article);
            $this->assertArrayHasKey('tagList', $article);
            $this->assertIsArray($article['tagList']);
            foreach ($article['tagList'] as $tag) {
                $this->assertSame($article['id'], $tag['articleId']);
            }
        }

        $batchedTagQueries = array_values(array_filter(
            $this->sql->queryLog,
            static fn (array $entry): bool => $entry['sqlId'] === 'tag_list_by_articles',
        ));
        $perArticleTagQueries = array_values(array_filter(
            $this->sql->queryLog,
            static fn (array $entry): bool => $entry['sqlId'] === 'tag_list_by_article',
        ));

        $this->assertCount(1, $batchedTagQueries);
        $this->assertCount(0, $perArticleTagQueries);
    }
}
