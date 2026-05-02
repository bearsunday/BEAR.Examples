<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;

use function assert;

final class IndexTest extends AbstractPageTestCase
{
    public function testAdminIndexRendersAdminEntryPoints(): void
    {
        $ro = $this->resource->get('page://self/admin/index');
        assert($ro instanceof Index);

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();

        $this->assertStringContainsString('<h1 class="AdminIndex">Admin</h1>', $html);
        $this->assertStringContainsString('class="goAdminArticleList"', $html);
        $this->assertStringContainsString('href="/admin/articlelist"', $html);
        $this->assertStringContainsString('class="goArticleList"', $html);
        $this->assertStringContainsString('class="goIndex"', $html);
    }
}
