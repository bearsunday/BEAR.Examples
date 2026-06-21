<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\Kata\AbstractAppTestCase;

use function uniqid;

final class TagTest extends AbstractAppTestCase
{
    public function testListAndGet(): void
    {
        $list = $this->resource->get('app://self/tags', []);
        $this->assertSame(200, $list->code);
        $this->assertGreaterThan(0, $list->body['count']);

        $one = $this->resource->get('app://self/tag', ['id' => 1]);
        $this->assertSame(200, $one->code);
        $this->assertSame('bear-sunday', $one->body['slug']);
    }

    public function testCreateAndDelete(): void
    {
        $post = $this->resource->post('app://self/tag', [
            'slug' => 'test-tag-' . uniqid(),
            'name' => 'Test Tag',
        ]);
        $this->assertSame(201, $post->code);

        $del = $this->resource->delete('app://self/tag', ['id' => $post->body['id']]);
        $this->assertSame(204, $del->code);
    }
}
