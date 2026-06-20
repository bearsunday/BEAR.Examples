<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page\Admin;

use BEAR\Examples\AbstractAdminPageTestCase;

final class IndexTest extends AbstractAdminPageTestCase
{
    public function testAdminIndexRendersHub(): void
    {
        $ro = $this->resource->get('page://self/admin/index');

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AdminIndex">Admin</h1>', $html);
        $this->assertStringContainsString('class="goAdminArticleList"', $html);
        $this->assertStringContainsString('class="doCreateArticle"', $html);
    }
}
