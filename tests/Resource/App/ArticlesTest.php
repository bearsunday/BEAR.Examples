<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

final class ArticlesTest extends AbstractAppTestCase
{
    public function testOnGetPaginatedWithDefaults(): void
    {
        $ro = $this->resource->get('app://self/articles', []);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['page']);
        $this->assertSame(20, $ro->body['perPage']);
        $this->assertGreaterThan(0, $ro->body['count']);
        $this->assertIsArray($ro->body['items']);
    }

    public function testFilterByStatus(): void
    {
        $ro = $this->resource->get('app://self/articles', [
            'perPage' => 50,
            'status' => 'draft',
        ]);

        foreach ($ro->body['items'] as $a) {
            $this->assertSame('draft', $a['status']);
        }
    }

    public function testFilterByCategoryId(): void
    {
        $ro = $this->resource->get('app://self/articles', [
            'perPage' => 50,
            'categoryId' => 1,
        ]);

        foreach ($ro->body['items'] as $a) {
            $this->assertSame(1, $a['categoryId']);
        }
    }

    public function testPerPageClampedToMax100(): void
    {
        $ro = $this->resource->get('app://self/articles', ['perPage' => 9999]);
        $this->assertSame(100, $ro->body['perPage']);
    }
}
