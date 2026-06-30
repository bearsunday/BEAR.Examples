<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer;

use BEAR\Defer\DeferInterface;
use Ray\Di\Di\Scope;

/**
 * Spy DeferInterface that captures deferred requests instead of executing them.
 * Records what was added, then executes them on flush() (synchronously).
 */
#[Scope('singleton')]
final class SpyDefer implements DeferInterface
{
    /** @var list<callable(): mixed> */
    public array $added = [];

    public function add(callable $request): void
    {
        $this->added[] = $request;
    }

    public function flush(): void
    {
        foreach ($this->added as $request) {
            $request();
        }
        $this->added = [];
    }
}
