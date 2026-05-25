<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

interface AllowedOriginInterface
{
    /**
     * Canonical origin (e.g. `https://cms.example.com`), or `null` to
     * short-circuit `SameOriginInterceptor` (dev / CLI / test).
     */
    public function value(): string|null;
}
