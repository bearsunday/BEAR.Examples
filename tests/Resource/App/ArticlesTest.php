<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

use function ceil;

final class ArticlesTest extends AbstractAppTestCase
{
    public function testOnGetPaginatedWithDefaults(): void
    {
        $ro = $this->resource->get('app://self/articles', []);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['page']);
        $this->assertSame(20, $ro->body['perPage']);
        $this->assertGreaterThan(0, $ro->body['count']);
        $this->assertGreaterThanOrEqual($ro->body['count'], $ro->body['totalCount']);
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

    public function testOutOfRangePageIsClampedToLastPage(): void
    {
        $ro = $this->resource->get('app://self/articles', ['page' => 99999, 'perPage' => 5]);

        $this->assertSame(200, $ro->code);
        $this->assertGreaterThan(0, $ro->body['totalCount']);
        $this->assertSame(
            (int) ceil($ro->body['totalCount'] / $ro->body['perPage']),
            $ro->body['page'],
        );
        $this->assertGreaterThan(0, $ro->body['count']);
    }

    public function testEmptyResultStillReturnsFirstPage(): void
    {
        $ro = $this->resource->get('app://self/articles', ['status' => 'no-such-status', 'page' => 5]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['page']);
        $this->assertSame(0, $ro->body['count']);
        $this->assertSame(0, $ro->body['totalCount']);
    }

    public function testTotalCountComesFromPagedResult(): void
    {
        $first = $this->resource->get('app://self/articles', ['perPage' => 5]);
        $second = $this->resource->get('app://self/articles', ['page' => 2, 'perPage' => 5]);

        $this->assertSame(5, $first->body['count']);
        $this->assertSame(5, $second->body['count']);
        $this->assertSame($first->body['totalCount'], $second->body['totalCount']);
        $this->assertGreaterThan($first->body['count'], $first->body['totalCount']);
    }
}
