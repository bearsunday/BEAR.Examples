<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;

final class IndexTest extends AbstractPageTestCase
{
    public function testAdminIndexRedirectsToArticleList(): void
    {
        $ro = $this->resource->get('page://self/admin/index');

        $this->assertSame(302, $ro->code);
        $this->assertSame('/admin/articlelist', $ro->headers['Location']);
        $this->assertSame('', $ro->toString());
    }
}
