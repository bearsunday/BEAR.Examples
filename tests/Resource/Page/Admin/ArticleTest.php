<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;

use function preg_match;
use function uniqid;

final class ArticleTest extends AbstractPageTestCase
{
    public function testCreateFormRendersWritableFields(): void
    {
        $ro = $this->resource->get('page://self/admin/article');

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AdminArticle">Create Article</h1>', $html);
        $this->assertStringContainsString('<form class="ArticleForm" method="post" action="/admin/article">', $html);
        $this->assertStringContainsString('name="slug"', $html);
        $this->assertStringContainsString('name="authorId"', $html);
        $this->assertStringContainsString('name="categoryId"', $html);
        $this->assertStringContainsString('name="tagIds[]"', $html);
    }

    public function testCreateUpdateRoundTripRedirectsAndPersists(): void
    {
        $slug = 'admin-created-' . uniqid();
        $created = $this->resource->post('page://self/admin/article', [
            'slug' => $slug,
            'title' => 'Admin Created Article',
            'body' => 'Created from the admin page resource.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
            'excerpt' => '',
            'publishedAt' => '',
            'tagIds' => [1, 2],
        ]);

        $this->assertSame(303, $created->code);
        $this->assertMatchesRegularExpression('#^/admin/article\?id=\d+&saved=created$#', $created->headers['Location']);
        $this->assertSame('', $created->toString());
        $this->assertSame(1, preg_match('/id=(\d+)/', (string) $created->headers['Location'], $matches));
        $id = (int) $matches[1];

        $article = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(200, $article->code);
        $this->assertSame($slug, $article->body['slug']);
        $this->assertSame('Admin Created Article', $article->body['title']);
        $this->assertNull($article->body['excerpt']);

        $edit = $this->resource->get('page://self/admin/article', ['id' => $id, 'saved' => 'created']);
        $this->assertSame(200, $edit->code);
        $editHtml = $edit->toString();
        $this->assertStringContainsString('<h1 class="AdminArticle">Edit Article</h1>', $editHtml);
        $this->assertStringContainsString('<p class="notice">Article created.</p>', $editHtml);
        $this->assertStringContainsString('<input type="hidden" name="id" value="' . $id . '">', $editHtml);
        $this->assertStringContainsString('<p class="slug">Slug: <code>' . $slug . '</code></p>', $editHtml);

        $updated = $this->resource->post('page://self/admin/article', [
            'id' => $id,
            'title' => 'Admin Updated Article',
            'body' => 'Updated from the admin page resource.',
            'status' => 'published',
            'excerpt' => 'Updated excerpt',
            'publishedAt' => '2026-02-01T10:00:00Z',
            'tagIds' => [3],
        ]);

        $this->assertSame(303, $updated->code);
        $this->assertSame('/admin/article?id=' . $id . '&saved=updated', $updated->headers['Location']);
        $this->assertSame('', $updated->toString());

        $articleAfterUpdate = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(200, $articleAfterUpdate->code);
        $this->assertSame('Admin Updated Article', $articleAfterUpdate->body['title']);
        $this->assertSame('published', $articleAfterUpdate->body['status']);
        $this->assertSame('Updated excerpt', $articleAfterUpdate->body['excerpt']);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testInvalidCreateReturnsFormWithEscapedValues(): void
    {
        $ro = $this->resource->post('page://self/admin/article', [
            'slug' => 'Invalid Slug',
            'title' => '<script>alert(1)</script>',
            'body' => 'Body',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);

        $this->assertSame(422, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<section class="ErrorList">', $html);
        $this->assertStringContainsString('value="&lt;script&gt;alert', $html);
        $this->assertStringNotContainsString('value="<script>alert(1)</script>"', $html);
    }

    public function testMissingArticleReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/article', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }
}
