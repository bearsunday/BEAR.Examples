<?php

declare(strict_types=1);

namespace Ray\Csrf\Http;

/**
 * Canonical origin the CSRF defence stack is configured for.
 *
 * `value === null` short-circuits both `SameOriginInterceptor` and
 * `CsrfTokenInterceptor` — used by tests / CLI / dev where there's
 * no browser on the other side. The consumer resolves the value once
 * (typically from an env var) and binds an instance.
 */
final readonly class AllowedOrigin
{
    public function __construct(public string|null $value = null)
    {
    }
}
