<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

final class IndexTest extends AbstractAppTestCase
{
    public function testEntryPoint(): void
    {
        $ro = $this->resource->get('app://self/', []);
        $this->assertSame(200, $ro->code);
        $this->assertSame('BEAR.Cms', $ro->body['name']);
    }
}
