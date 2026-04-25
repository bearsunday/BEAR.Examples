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

        // The HAL renderer materialises #[Embed] requests under _embedded.
        $rendered = json_decode((string) $ro, true);
        $this->assertSame($ro->body['authorId'], $rendered['_embedded']['goAuthor']['id']);
        $this->assertSame($ro->body['categoryId'], $rendered['_embedded']['goCategory']['id']);
        $this->assertIsArray($rendered['_embedded']['goTagList']['items']);

        // _links carry the URI templates expanded with request arguments.
        $this->assertArrayHasKey('goArticleList', $rendered['_links']);
        $this->assertArrayHasKey('goAuthor', $rendered['_links']);
        $this->assertArrayHasKey('goCategory', $rendered['_links']);
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

    public function testCreateWithTagsLinksThemAndUpdateReplaces(): void
    {
        $slug = 'tagged-' . uniqid();
        $post = $this->resource->post('app://self/article', [
            'slug' => $slug,
            'title' => 'Tagged',
            'body' => 'B',
            'authorId' => 1,
            'categoryId' => 1,
            'status' => 'published',
            'tagIds' => [1, 2, 3],
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $rendered = json_decode((string) $get, true);
        $tagsAfterCreate = array_column($rendered['_embedded']['goTagList']['items'], 'id');
        sort($tagsAfterCreate);
        $this->assertSame([1, 2, 3], $tagsAfterCreate);

        $put = $this->resource->put('app://self/article', [
            'id' => $id,
            'title' => 'Tagged',
            'body' => 'B',
            'status' => 'published',
            'tagIds' => [4, 5],
        ]);
        $this->assertSame(200, $put->code);

        $getAgain = $this->resource->get('app://self/article', ['id' => $id]);
        $renderedAgain = json_decode((string) $getAgain, true);
        $tagsAfterUpdate = array_column($renderedAgain['_embedded']['goTagList']['items'], 'id');
        sort($tagsAfterUpdate);
        $this->assertSame([4, 5], $tagsAfterUpdate);

        $this->resource->delete('app://self/article', ['id' => $id]);
    }
}
