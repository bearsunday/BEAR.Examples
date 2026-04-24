<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

final class CategoryTest extends AbstractAppTestCase
{
    public function testGetAndNotFound(): void
    {
        $ro = $this->resource->get('app://self/category', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertSame('technology', $ro->body['slug']);

        $miss = $this->resource->get('app://self/category', ['id' => 99999]);
        $this->assertSame(404, $miss->code);
    }

    public function testCreateUpdateDelete(): void
    {
        $slug = 'test-cat-' . uniqid();
        $post = $this->resource->post('app://self/category', [
            'slug' => $slug,
            'name' => 'Test Category',
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $put = $this->resource->put('app://self/category', [
            'id' => $id,
            'name' => 'Renamed',
        ]);
        $this->assertSame(200, $put->code);

        $getAfter = $this->resource->get('app://self/category', ['id' => $id]);
        $this->assertSame('Renamed', $getAfter->body['name']);

        $del = $this->resource->delete('app://self/category', ['id' => $id]);
        $this->assertSame(204, $del->code);
    }
}
