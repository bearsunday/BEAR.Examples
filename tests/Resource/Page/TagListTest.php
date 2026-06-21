<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Kata\AbstractPageTestCase;

use function assert;

final class TagListTest extends AbstractPageTestCase
{
    public function testOnGetReturnsTagList(): void
    {
        $ro = $this->resource->get('page://self/taglist');
        assert($ro instanceof TagList);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="TagList">Tags</h1>', $html);
        $this->assertStringContainsString('class="goTag"', $html);
        $this->assertStringContainsString('href="/tag?id=', $html);
    }
}
