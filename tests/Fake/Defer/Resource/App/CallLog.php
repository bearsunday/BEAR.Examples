<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

use Ray\Di\Di\Scope;

/**
 * Spy that records which follow-up resources were called.
 * Singleton so the test can inspect the log after flush.
 */
#[Scope('singleton')]
final class CallLog
{
    /** @var list<string> */
    public array $calls = [];

    public function call(string $name): void
    {
        $this->calls[] = $name;
    }
}
