<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Variations;

use BEAR\Kata\AbstractAppTestCase;

final class ArticleSqlQueryTest extends AbstractAppTestCase
{
    public function testOnGetAddsReadingTimeAndAdjacentArticles(): void
    {
        $ro = $this->resource->get('app://self/variations/articlesqlquery', ['id' => 2]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(2, $ro->body['id']);
        $this->assertSame(1, $ro->body['readingTimeMinutes']);

        // The adjacent ids come from deterministic fake fixtures generated with random.seed(42).
        $this->assertSame(25, $ro->body['previous']['id']);
        $this->assertSame(38, $ro->body['next']['id']);
    }
}
