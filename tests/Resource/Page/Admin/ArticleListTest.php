<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;

final class ArticleListTest extends AbstractPageTestCase
{
    public function testAdminArticleListRendersArticleActions(): void
    {
        $ro = $this->resource->get('page://self/admin/articlelist');

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AdminArticleList">Article Administration</h1>', $html);
        $this->assertStringContainsString('class="doCreateArticle" href="/admin/article"', $html);
        $this->assertStringContainsString('class="doUpdateArticle" href="/admin/article?id=', $html);
        $this->assertStringContainsString('class="doDeleteArticle" href="/admin/articledelete?id=', $html);
        $this->assertStringContainsString('class="goArticle" href="/article?id=', $html);
    }

    public function testAdminArticleListKeepsStatusFilterAndPagination(): void
    {
        $ro = $this->resource->get('page://self/admin/articlelist', [
            'status' => 'draft',
            'perPage' => 5,
        ]);

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<option value="draft" selected>Draft</option>', $html);
        $this->assertStringContainsString('href="/admin/articlelist?status=draft&amp;page=2&amp;perPage=5"', $html);
        $this->assertStringContainsString('<span class="perPage">5</span>', $html);
    }

    public function testAdminArticleListShowsDeletedNotice(): void
    {
        $ro = $this->resource->get('page://self/admin/articlelist', ['deleted' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('<p class="notice">Article deleted.</p>', $ro->toString());
    }
}
