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
        $this->assertSame(1, $rendered['_embedded']['goAuthor']['id']);
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
            'tagIds' => [1, 2],
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $get = $this->resource->get('app://self/article', ['id' => $id]);
        $rendered = json_decode((string) $get, true);
        $tagIds = array_column($rendered['_embedded']['goTagList']['items'], 'id');
        sort($tagIds);
        $this->assertSame([1, 2], $tagIds);

        $del = $this->resource->delete('app://self/article', ['id' => $id]);
        $this->assertSame(204, $del->code);
    }
}
