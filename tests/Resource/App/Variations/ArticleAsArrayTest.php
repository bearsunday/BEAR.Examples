<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use MyVendor\Cms\AbstractAppTestCase;

final class ArticleAsArrayTest extends AbstractAppTestCase
{
    public function testOnGetReturnsArticleBodyWithoutEntityMapping(): void
    {
        $ro = $this->resource->get('app://self/variations/articleasarray', ['id' => 1]);

        $this->assertSame(200, $ro->code);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('getting-started-with-bear-sunday', $ro->body['slug']);
        $this->assertSame(1, $ro->body['authorId']);
        $this->assertArrayNotHasKey('readingTimeMinutes', $ro->body);
    }
}
