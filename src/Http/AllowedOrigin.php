<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

/**
 * Canonical origin the CSRF defence stack is configured for.
 *
 * `value === null` short-circuits both `SameOriginInterceptor` and
 * `CsrfTokenInterceptor` — used by tests / CLI / dev where there's
 * no browser on the other side. AppModule resolves the value once
 * from `CMS_ALLOWED_ORIGIN`.
 */
final readonly class AllowedOrigin
{
    public function __construct(public string|null $value = null)
    {
    }
}
