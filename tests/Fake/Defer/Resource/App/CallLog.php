<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

/**
 * Spy that records which follow-up resources were called.
 * Singleton so the test can inspect the log after flush.
 */
final class CallLog
{
    /** @var list<string> */
    public array $calls = [];

    public function call(string $name): void
    {
        $this->calls[] = $name;
    }
}
