<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;

class Index extends ResourceObject
{
    /** @var array{name: string} */
    public $body;

    public function onGet(string $name = 'BEAR.Sunday'): static
    {
        $this->body = ['name' => $name];

        return $this;
    }
}
