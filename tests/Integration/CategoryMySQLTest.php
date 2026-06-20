<?php

declare(strict_types=1);

namespace BEAR\Examples\Integration;

use function uniqid;

final class CategoryMySQLTest extends AbstractMySQLTestCase
{
    public function testReadAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/category', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertSame('technology', $ro->body['slug']);
    }

    public function testListAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/categories', []);
        $this->assertSame(200, $ro->code);
        $this->assertGreaterThan(0, $ro->body['count']);
    }

    public function testCreateUpdateDeleteAgainstRealDb(): void
    {
        $slug = 'integration-cat-' . uniqid();
        $post = $this->resource->post('app://self/category', [
            'slug' => $slug,
            'name' => 'Integration Category',
            'description' => 'Created by integration test.',
        ]);
        $this->assertSame(201, $post->code);
        $id = $post->body['id'];

        $put = $this->resource->put('app://self/category', [
            'id' => $id,
            'name' => 'Renamed Category',
            'description' => 'Updated description.',
        ]);
        $this->assertSame(200, $put->code);

        $get = $this->resource->get('app://self/category', ['id' => $id]);
        $this->assertSame('Renamed Category', $get->body['name']);

        $del = $this->resource->delete('app://self/category', ['id' => $id]);
        $this->assertSame(204, $del->code);
    }

    public function testNestedParentReachableAgainstRealDb(): void
    {
        // Seed has some categories with parentId set; verify the field round-trips.
        $ro = $this->resource->get('app://self/categories', []);
        $this->assertSame(200, $ro->code);
        $hasParent = false;
        foreach ($ro->body['items'] as $item) {
            if ($item['parentId'] !== null) {
                $hasParent = true;
                break;
            }
        }

        $this->assertTrue($hasParent, 'Seed should include at least one category with parentId set.');
    }
}
