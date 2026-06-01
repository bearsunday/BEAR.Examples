<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractAdminPageTestCase;

final class ArticleListTest extends AbstractAdminPageTestCase
{
    public function testAdminArticleListRendersArticleActions(): void
    {
        $ro = $this->resource->get('page://self/admin/articlelist');

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AdminArticleList">Article Administration</h1>', $html);
        $this->assertStringContainsString('class="goAdminIndex"', $html);
        $this->assertStringContainsString('class="doCreateArticle" href="/admin/article"', $html);
        $this->assertStringContainsString('class="doUpdateArticle" href="/admin/article?id=', $html);
        $this->assertStringContainsString('class="doDeleteArticle" href="/admin/articledelete?id=', $html);
        $this->assertStringContainsString('class="goArticle" href="/article?id=', $html);
        $this->assertStringContainsString('getting-started-with-bear-sunday', $html);
        $this->assertStringNotContainsString('why-ray-mediaquery-changes-how-we-write-sql', $html);
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
        $this->assertStringNotContainsString('class="goNext"', $html);
        $this->assertStringContainsString('<span class="perPage">5</span>', $html);
        $this->assertStringNotContainsString('<script>alert("xss")</script>Bad', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function testAdminArticleListShowsDeletedNotice(): void
    {
        $ro = $this->resource->get('page://self/admin/articlelist', ['deleted' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('<p class="notice">Article deleted.</p>', $ro->toString());
    }
}
