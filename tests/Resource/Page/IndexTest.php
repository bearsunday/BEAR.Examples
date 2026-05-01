<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

class IndexTest extends AbstractPageTestCase
{
    public function testOnGetRendersArticleList(): void
    {
        $ro = $this->resource->get('page://self/index');
        assert($ro instanceof Index);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('<a class="goArticle" href="/article?id=1">', $html);
        $this->assertSame($html, $ro->view);
    }
}
