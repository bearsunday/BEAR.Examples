<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

class IndexTest extends AbstractPageTestCase
{
    public function testOnGet(): void
    {
        $ro = $this->resource->get('page://self/index', ['name' => 'BEAR.Sunday']);
        assert($ro instanceof Index);

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();

        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);
        $this->assertStringContainsString('<p>Hello BEAR.Sunday</p>', $html);
        $this->assertSame($html, $ro->view);
    }
}
