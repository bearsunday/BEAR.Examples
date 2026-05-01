<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

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
}
