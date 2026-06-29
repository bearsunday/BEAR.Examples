<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Observation\Resource\App;

use BEAR\Resource\ResourceObject;

/**
 * Minimal resource for the observation bridge test — no DB dependencies.
 */
class Hello extends ResourceObject
{
    public function onGet(string $name = 'World'): static
    {
        $this->body = ['greeting' => "Hello, {$name}!"];

        return $this;
    }
}
