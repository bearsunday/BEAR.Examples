<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Examples\AbstractPageTestCase;

use function assert;

final class ArticleListTest extends AbstractPageTestCase
{
    public function testOnGetReturnsHtmlWithArticleLinks(): void
    {
        $ro = $this->resource->get('page://self/articlelist');
        assert($ro instanceof ArticleList);

        $this->assertSame(200, $ro->code);

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
        $this->assertStringContainsString('data-status="published"', $html);
        $this->assertStringNotContainsString('data-status="draft"', $html);
    }

    public function testNextLinkAppearsWhenPageIsFull(): void
    {
        $ro = $this->resource->get('page://self/articlelist', ['perPage' => 5]);
        $html = $ro->toString();

        $this->assertStringContainsString('class="goNext"', $html);
        $this->assertStringContainsString('href="/articlelist?page=2&amp;perPage=5"', $html);
    }

    public function testPreviousLinkEscapesQueryStringAttributes(): void
    {
        $ro = $this->resource->get('page://self/articlelist', ['page' => 2, 'perPage' => 5]);
        $html = $ro->toString();

        $this->assertStringContainsString('class="goPrev"', $html);
        $this->assertStringContainsString('href="/articlelist?page=1&amp;perPage=5"', $html);
    }

    public function testStatusFilterCannotExposeDraftArticles(): void
    {
        $ro = $this->resource->get('page://self/articlelist', ['status' => 'draft', 'perPage' => 100]);
        $html = $ro->toString();

        $this->assertStringContainsString('data-status="published"', $html);
        $this->assertStringNotContainsString('data-status="draft"', $html);
        $this->assertStringNotContainsString('Filtered by status:', $html);
        $this->assertStringNotContainsString('<script>alert("xss")</script>Bad', $html);
    }
}
