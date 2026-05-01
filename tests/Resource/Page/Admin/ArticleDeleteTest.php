<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;

use function preg_match;
use function uniqid;

final class ArticleDeleteTest extends AbstractPageTestCase
{
    public function testDeleteConfirmationRendersArticle(): void
    {
        $ro = $this->resource->get('page://self/admin/articledelete', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AdminArticleDelete">Delete Article</h1>', $html);
        $this->assertStringContainsString('<form class="DeleteForm" method="post" action="/admin/articledelete">', $html);
        $this->assertStringContainsString('class="doDeleteArticle"', $html);
    }

    public function testDeleteRedirectsAndRemovesArticle(): void
    {
        $created = $this->resource->post('page://self/admin/article', [
            'slug' => 'admin-delete-' . uniqid(),
            'title' => 'Admin Delete Article',
            'body' => 'Created so the admin delete page can remove it.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);

        $this->assertSame(303, $created->code);
        $this->assertSame(1, preg_match('/id=(\d+)/', (string) $created->headers['Location'], $matches));
        $id = (int) $matches[1];

        $deleted = $this->resource->post('page://self/admin/articledelete', ['id' => $id]);
        $this->assertSame(303, $deleted->code);
        $this->assertSame('/admin/articlelist?deleted=1', $deleted->headers['Location']);
        $this->assertSame('', $deleted->toString());

        $missing = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(404, $missing->code);
    }

    public function testMissingDeleteTargetReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/articledelete', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }
}
