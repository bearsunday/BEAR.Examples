<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class ArticleFeedTest extends AbstractPageTestCase
{
    public function testOnGetReturnsArticleFeedItems(): void
    {
        $ro = $this->resource->get('page://self/articlefeed');
        assert($ro instanceof ArticleFeed);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="ArticleFeed">Article Feed</h1>', $html);
        $this->assertStringContainsString('class="ArticleFeedItem"', $html);
        $this->assertStringContainsString('class="postedAgo"', $html);
        $this->assertStringContainsString('ago', $html);
        $this->assertStringNotContainsString('draft', $html);
    }
}
