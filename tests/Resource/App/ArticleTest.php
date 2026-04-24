<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

final class ArticleTest extends AbstractAppTestCase
{
    public function testOnGetReturnsEmbeddedAuthorCategoryTags(): void
    {
        $ro = $this->resource->get('app://self/article', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('getting-started-with-bear-sunday', $ro->body['slug']);
        $this->assertArrayHasKey('_embedded', $ro->body);
        $this->assertSame($ro->body['authorId'], $ro->body['_embedded']['author']->id);
        $this->assertSame($ro->body['categoryId'], $ro->body['_embedded']['category']->id);
        $this->assertIsArray($ro->body['_embedded']['tags']);
    }

    public function testOnGetMissingReturns404(): void
    {
        $ro = $this->resource->get('app://self/article', ['id' => 99999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('Article not found', $ro->body['message']);
    }

    public function testCreateUpdateDeleteRoundTrip(): int
    {
        $post = $this->resource->post('app://self/article', [
            'slug' => 'test-article-' . uniqid(),
            'title' => 'Test Article',
            'body' => 'Body content.',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'draft',
        ]);
        $this->assertSame(201, $post->code);
        $this->assertIsInt($post->body['id']);
        $this->assertStringStartsWith('/article?id=', (string) $post->headers['Location']);

        $id = $post->body['id'];
        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(200, $get->code);
        $this->assertSame('Test Article', $get->body['title']);

        $put = $this->resource->put('app://self/article', [
            'id' => $id,
            'title' => 'Updated Title',
            'body' => 'Updated body.',
            'status' => 'published',
        ]);
        $this->assertSame(200, $put->code);

        $getAfter = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame('Updated Title', $getAfter->body['title']);
        $this->assertSame('published', $getAfter->body['status']);

        $delete = $this->resource->delete('app://self/article', ['id' => $id]);
        $this->assertSame(204, $delete->code);

        $getMissing = $this->resource->get('app://self/article', ['id' => $id]);
        $this->assertSame(404, $getMissing->code);

        return $id;
    }

    public function testPutOnMissingReturns404(): void
    {
        $ro = $this->resource->put('app://self/article', [
            'id' => 99999,
            'title' => 't',
            'body' => 'b',
            'status' => 'draft',
        ]);
        $this->assertSame(404, $ro->code);
    }

    public function testDeleteOnMissingReturns404(): void
    {
        $ro = $this->resource->delete('app://self/article', ['id' => 99999]);
        $this->assertSame(404, $ro->code);
    }
}
