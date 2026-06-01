<?php

declare(strict_types=1);

namespace MyVendor\Cms\Integration;

use function array_column;
use function json_decode;
use function sort;
use function uniqid;

final class ArticleMySQLTest extends AbstractMySQLTestCase
{
    public function testReadAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/article', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertSame('getting-started-with-bear-sunday', $ro->body['slug']);

        $rendered = json_decode((string) $ro, true);
        $this->assertArrayHasKey('_embedded', $rendered);
        $this->assertSame(1, $rendered['_embedded']['author']['id']);
    }

    public function testWriteRoundTripAgainstRealDb(): void
    {
        $slug = 'integration-' . uniqid();
        $post = $this->resource->post('app://self/article', [
            'slug' => $slug,
            'title' => 'Integration test article',
            'body' => 'Body for the integration test path.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'published',
            'publishedAt' => '2026-06-01T04:43:50Z',
            'tagIds' => [1, 2],
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('2026-06-01T04:43:50Z', $get->body['publishedAt']);
        $rendered = json_decode((string) $get, true);
        $tagIds = array_column($rendered['_embedded']['tagList']['items'], 'id');
        sort($tagIds);
        $this->assertSame([1, 2], $tagIds);

        $put = $this->resource->put('app://self/article', [
            'id' => $id,
            'title' => 'Integration test article updated',
            'body' => 'Updated body for the integration test path.',
            'status' => 'published',
            'publishedAt' => '2026-06-01T13:43:50+09:00',
        ]);
        $this->assertSame(200, $put->code);

        $getAfterPut = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('2026-06-01T04:43:50Z', $getAfterPut->body['publishedAt']);

        $del = $this->resource->delete('app://self/article', ['id' => $id]);
        $this->assertSame(204, $del->code);
    }

    public function testPublishDraftAgainstRealDb(): void
    {
        $slug = 'integration-publish-' . uniqid();
        $post = $this->resource->post('app://self/article', [
            'slug' => $slug,
            'title' => 'Integration publish article',
            'body' => 'Body for the integration publish path.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $published = $this->resource->post('app://self/article-publish', ['id' => $id]);
        $this->assertSame(200, $published->code);
        $this->assertSame('published', $published->body['status']);

        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('published', $get->body['status']);
        $this->assertNotNull($get->body['publishedAt']);

        $del = $this->resource->delete('app://self/article', ['id' => $id]);
        $this->assertSame(204, $del->code);
    }
}
