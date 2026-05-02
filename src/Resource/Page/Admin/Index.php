<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;

/** @property array{} $body */
class Index extends ResourceObject
{
    public function onGet(): static
    {
        $this->body = [];

        return $this;
    }
}
