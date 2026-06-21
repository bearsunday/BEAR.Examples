<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Kata\AbstractPageTestCase;

use function assert;

final class CategoryListTest extends AbstractPageTestCase
{
    public function testOnGetReturnsCategoryList(): void
    {
        $ro = $this->resource->get('page://self/categorylist');
        assert($ro instanceof CategoryList);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="CategoryList">Categories</h1>', $html);
        $this->assertStringContainsString('class="goCategory"', $html);
        $this->assertStringContainsString('href="/category?id=', $html);
    }
}
