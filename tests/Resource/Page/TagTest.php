<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class TagTest extends AbstractPageTestCase
{
    public function testOnGetReturnsTagDetail(): void
    {
        $ro = $this->resource->get('page://self/tag', ['id' => 1]);
        assert($ro instanceof Tag);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<section class="Tag">', $html);
        $this->assertStringContainsString('class="goTagList"', $html);
        $this->assertStringContainsString('class="goArticleList"', $html);
    }

    public function testNotFoundReturns404(): void
    {
        $ro = $this->resource->get('page://self/tag', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
    }
}
