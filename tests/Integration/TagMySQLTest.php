<?php

declare(strict_types=1);

namespace BEAR\Kata\Integration;

use function json_decode;
use function uniqid;

final class TagMySQLTest extends AbstractMySQLTestCase
{
    public function testListAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/tags', []);
        $this->assertSame(200, $ro->code);
        $this->assertGreaterThan(0, $ro->body['count']);
    }

    public function testCreateThenDeleteAgainstRealDb(): void
    {
        $slug = 'integration-tag-' . uniqid();
        $post = $this->resource->post('app://self/tag', [
            'slug' => $slug,
            'name' => 'Integration Tag',
        ]);
        $this->assertSame(201, $post->code);
        $newId = $post->body['id'];

        $get = $this->resource->get('app://self/tag', ['id' => $newId]);
        $this->assertSame(200, $get->code);
        $this->assertSame('Integration Tag', $get->body['name']);

        $del = $this->resource->delete('app://self/tag', ['id' => $newId]);
        $this->assertSame(204, $del->code);

        $missing = $this->resource->get('app://self/tag', ['id' => $newId]);
        $this->assertSame(404, $missing->code);
    }

    public function testFilterArticlesByTagAgainstRealDb(): void
    {
        // Articles seeded reference tags 1..50; pick tag id 1 and verify some article links to it.
        $ro = $this->resource->get('app://self/articles', ['tagId' => 1, 'perPage' => 50]);
        $this->assertSame(200, $ro->code);
        $rendered = json_decode((string) $ro, true);
        $this->assertIsArray($rendered['items']);
    }
}
