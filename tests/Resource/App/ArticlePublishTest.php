<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

use function uniqid;

final class ArticlePublishTest extends AbstractAppTestCase
{
    public function testPublishesDraftArticle(): void
    {
        $created = $this->resource->post('app://self/article', [
            'slug' => 'publish-test-' . uniqid(),
            'title' => 'Publish test',
            'body' => 'Body.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $id = (int) $created->body['id'];

        $published = $this->resource->post('app://self/article-publish', ['id' => $id]);

        $this->assertSame(200, $published->code);
        $this->assertSame($id, $published->body['id']);
        $this->assertSame('published', $published->body['status']);
        $this->assertNotEmpty($published->body['publishedAt']);

        // Round-trip: the state actually moved.
        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('published', $get->body['status']);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testPublishAcceptsExplicitPublishedAt(): void
    {
        $created = $this->resource->post('app://self/article', [
            'slug' => 'publish-backdated-' . uniqid(),
            'title' => 'Backdated',
            'body' => 'Body.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $id = (int) $created->body['id'];

        $stamp = '2020-01-02T03:04:05Z';
        $published = $this->resource->post('app://self/article-publish', [
            'id' => $id,
            'publishedAt' => $stamp,
        ]);

        $this->assertSame(200, $published->code);
        $this->assertSame($stamp, $published->body['publishedAt']);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }

    public function testPublishMissingReturns404(): void
    {
        $ro = $this->resource->post('app://self/article-publish', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }

    public function testRepublishingAlreadyPublishedReturns409(): void
    {
        $created = $this->resource->post('app://self/article', [
            'slug' => 'publish-twice-' . uniqid(),
            'title' => 'Already published',
            'body' => 'Body.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'published',
        ]);
        $id = (int) $created->body['id'];

        $second = $this->resource->post('app://self/article-publish', ['id' => $id]);

        $this->assertSame(409, $second->code);
        $this->assertSame('Article is already published', $second->body['message']);
        $this->assertSame('published', $second->body['status']);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }
}
