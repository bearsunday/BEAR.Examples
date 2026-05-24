<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractAdminPageTestCase;

use function uniqid;

final class ArticleConfirmTest extends AbstractAdminPageTestCase
{
    public function testGetShowsDraftPreviewWithPublishForm(): void
    {
        $id = $this->createDraft();

        $ro = $this->resource->get('page://self/admin/articleconfirm', ['id' => $id]);

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<h1 class="AdminArticleConfirm">Publish Article</h1>', $html);
        $this->assertStringContainsString('<form class="PublishForm" method="post" action="/admin/articleconfirm">', $html);
        $this->assertStringContainsString('class="doPublishArticle"', $html);
        $this->assertStringContainsString('data-status="draft"', $html);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testGetAlreadyPublishedShowsNoticeNotForm(): void
    {
        $id = $this->createPublished();

        $ro = $this->resource->get('page://self/admin/articleconfirm', ['id' => $id]);

        $this->assertSame(200, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('already-published', $html);
        $this->assertStringNotContainsString('<form class="PublishForm"', $html);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testGetMissingReturns404(): void
    {
        $ro = $this->resource->get('page://self/admin/articleconfirm', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }

    public function testPostPublishesDraftAndRedirectsToPublicArticle(): void
    {
        $id = $this->createDraft();

        $ro = $this->resource->post('page://self/admin/articleconfirm', ['id' => $id]);

        $this->assertSame(303, $ro->code);
        $this->assertSame('/article?id=' . $id, $ro->headers['Location']);

        // State actually moved.
        $article = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('published', $article->body['status']);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testPostOnAlreadyPublishedReturns409WithPreviewBack(): void
    {
        $id = $this->createPublished();

        $ro = $this->resource->post('page://self/admin/articleconfirm', ['id' => $id]);

        $this->assertSame(409, $ro->code);
        $html = $ro->toString();
        $this->assertStringContainsString('<section class="ErrorList">', $html);
        $this->assertStringContainsString('already published', $html);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testPostMissingReturns404(): void
    {
        $ro = $this->resource->post('page://self/admin/articleconfirm', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }

    public function testEditFormOfDraftLinksToConfirm(): void
    {
        $id = $this->createDraft();

        $ro = $this->resource->get('page://self/admin/article', ['id' => $id]);

        $this->assertStringContainsString('/admin/articleconfirm?id=' . $id, $ro->toString());

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testEditFormOfPublishedHidesConfirmLink(): void
    {
        $id = $this->createPublished();

        $ro = $this->resource->get('page://self/admin/article', ['id' => $id]);

        $this->assertStringNotContainsString('/admin/articleconfirm?id=' . $id, $ro->toString());

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testConfirmOtherAuthorsArticleReturns403(): void
    {
        // Article id=2 in the fake fixtures is owned by a different author
        // than the test admin (authorId=1) — mirrors the pattern in
        // ArticleTest::testUpdateOtherAuthorsArticleReturns403 and
        // ArticleDeleteTest::testDeleteOtherAuthorsArticleReturns403.
        $preview = $this->resource->get('page://self/admin/articleconfirm', ['id' => 2]);
        $this->assertSame(403, $preview->code);
        $this->assertSame('Forbidden', $preview->body['message']);

        $publish = $this->resource->post('page://self/admin/articleconfirm', ['id' => 2]);
        $this->assertSame(403, $publish->code);
        $this->assertSame('Forbidden', $publish->body['message']);
    }

    private function createDraft(): int
    {
        $created = $this->resource->post('app://self/article', [
            'slug' => 'confirm-draft-' . uniqid(),
            'title' => 'Confirm draft',
            'body' => 'Body content.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);

        return (int) $created->body['id'];
    }

    private function createPublished(): int
    {
        $created = $this->resource->post('app://self/article', [
            'slug' => 'confirm-published-' . uniqid(),
            'title' => 'Confirm published',
            'body' => 'Body content.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'published',
        ]);

        return (int) $created->body['id'];
    }
}
