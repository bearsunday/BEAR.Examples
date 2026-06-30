<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

use BEAR\Resource\ResourceObject;

/**
 * Follow-up resource — does not know it is deferred.
 */
class Note extends ResourceObject
{
    public function __construct(
        private readonly CallLog $log,
    ) {
    }

    public function onPost(string $id): static
    {
        $this->log->call("note:{$id}");

        return $this;
    }
}
