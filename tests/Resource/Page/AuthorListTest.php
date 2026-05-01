<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class AuthorListTest extends AbstractPageTestCase
{
    public function testOnGetReturnsAuthorList(): void
    {
        $ro = $this->resource->get('page://self/authorlist');
        assert($ro instanceof AuthorList);

        $this->assertSame(200, $ro->code);

        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AuthorList">Authors</h1>', $html);
        $this->assertStringContainsString('class="goAuthor"', $html);
        $this->assertStringContainsString('href="/author?id=', $html);
    }
}
