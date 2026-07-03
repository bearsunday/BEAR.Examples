<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\Kata\AbstractAppTestCase;

use function count;

final class AuthorsTest extends AbstractAppTestCase
{
    public function testOnGetReturnsAuthorCollection(): void
    {
        $ro = $this->resource->get('app://self/authors', []);

        $this->assertSame(200, $ro->code);
        $this->assertIsArray($ro->body['items']);
        $this->assertGreaterThan(0, $ro->body['count']);
        $this->assertSame($ro->body['count'], count($ro->body['items']));
    }

    public function testItemsExposeAuthorFields(): void
    {
        $ro = $this->resource->get('app://self/authors', []);

        $first = $ro->body['items'][0];
        $this->assertArrayHasKey('id', $first);
        $this->assertArrayHasKey('name', $first);
        $this->assertArrayHasKey('email', $first);
        $this->assertArrayHasKey('bio', $first);
    }
}
