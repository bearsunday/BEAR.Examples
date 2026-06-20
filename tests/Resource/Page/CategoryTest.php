<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Examples\AbstractPageTestCase;

use function assert;

final class CategoryTest extends AbstractPageTestCase
{
    public function testOnGetReturnsCategoryDetailWithArticles(): void
    {
        $ro = $this->resource->get('page://self/category', ['id' => 1]);
        assert($ro instanceof Category);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<section class="Category">', $html);
        $this->assertStringContainsString('class="name"', $html);
        $this->assertStringContainsString('class="slug"', $html);
        $this->assertStringContainsString('class="goArticleList"', $html);
        $this->assertStringContainsString('class="goCategoryList"', $html);
    }

    public function testNotFoundReturns404(): void
    {
        $ro = $this->resource->get('page://self/category', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
    }

    public function testNotFoundRendersErrorTemplate(): void
    {
        $ro = $this->resource->get('page://self/category', ['id' => 99999]);
        $html = $ro->toString();

        $this->assertStringContainsString('<h1>Error 404</h1>', $html);
        $this->assertStringContainsString('An unexpected error occurred.', $html);
        $this->assertStringNotContainsString('<section class="Category">', $html);
    }
}
