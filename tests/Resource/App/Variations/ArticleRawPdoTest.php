<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Variations;

use BEAR\Kata\AbstractAppTestCase;

final class ArticleRawPdoTest extends AbstractAppTestCase
{
    public function testOnGetReturnsArticleThroughRawPdo(): void
    {
        $ro = $this->resource->get('app://self/variations/articlerawpdo', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('Getting Started with BEAR.Sunday', $ro->body['title']);
        $this->assertSame('2026-01-01T10:07:00Z', $ro->body['publishedAt']);
    }
}
