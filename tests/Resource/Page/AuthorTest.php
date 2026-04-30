<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class AuthorTest extends AbstractPageTestCase
{
    public function testOnGetReturnsAuthorDetail(): void
    {
        $ro = $this->resource->get('page://self/author', ['id' => 1]);
        assert($ro instanceof Author);

        $this->assertSame(200, $ro->code);
        $this->assertSame('text/html; charset=utf-8', $ro->headers['Content-Type']);

        $html = $ro->toString();
        $this->assertStringContainsString('<section class="Author">', $html);
        $this->assertStringContainsString('class="name"', $html);
        $this->assertStringContainsString('class="email"', $html);
        $this->assertStringContainsString('class="bio"', $html);
        $this->assertStringContainsString('class="goArticleList"', $html);
    }

    public function testNotFoundReturns404(): void
    {
        $ro = $this->resource->get('page://self/author', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
    }
}
