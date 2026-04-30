<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class ArticleListTest extends AbstractPageTestCase
{
    public function testOnGetReturnsHtmlWithArticleLinks(): void
    {
        $ro = $this->resource->get('page://self/articlelist');
        assert($ro instanceof ArticleList);

        $this->assertSame(200, $ro->code);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);

        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="ArticleList">Article List</h1>', $html);
        $this->assertStringContainsString('class="goArticle"', $html);
        $this->assertStringContainsString('href="/article?id=', $html);
    }

    public function testCategoryFilterShowsCategoryName(): void
    {
        $ro = $this->resource->get('page://self/articlelist', ['categoryId' => 1]);
        $html = $ro->toString();

        $this->assertStringContainsString('Filtered by category:', $html);
    }

    public function testNextLinkAppearsWhenPageIsFull(): void
    {
        $ro = $this->resource->get('page://self/articlelist', ['perPage' => 5]);
        $html = $ro->toString();

        $this->assertStringContainsString('class="goNext"', $html);
    }

    public function testListEscapesArticleTitles(): void
    {
        $ro = $this->resource->get('page://self/articlelist', ['perPage' => 100]);
        $html = $ro->toString();

        $this->assertStringNotContainsString('<script>alert("xss")</script>Bad', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }
}
