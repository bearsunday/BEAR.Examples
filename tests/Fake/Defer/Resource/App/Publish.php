<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

use BEAR\Resource\ResourceObject;

/**
 * Follow-up resource — does not know it is deferred.
 */
class Publish extends ResourceObject
{
    public function __construct(
        private readonly CallLog $log,
    ) {
    }

    public function onPost(string $id): static
    {
        $this->log->call("publish:{$id}");

        return $this;
    }
}
