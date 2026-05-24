<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

use function is_string;

/**
 * `$_SERVER`-backed `RequestOriginInterface`.
 *
 * Boundary class for request-header access — the rest of the application
 * reads origin signals through the interface. PHP exposes inbound HTTP
 * headers as `HTTP_*` keys on `$_SERVER`; this adapter is the only place
 * those keys are touched.
 *
 * @SuppressWarnings("PHPMD.Superglobals") Header adapter boundary; mirrors the `NativeAuthSession` justification.
 */
final readonly class ServerRequestOrigin implements RequestOriginInterface
{
    #[Override]
    public function fetchSite(): string|null
    {
        return $this->headerValue('HTTP_SEC_FETCH_SITE');
    }

    #[Override]
    public function origin(): string|null
    {
        return $this->headerValue('HTTP_ORIGIN');
    }

    #[Override]
    public function referer(): string|null
    {
        return $this->headerValue('HTTP_REFERER');
    }

    private function headerValue(string $key): string|null
    {
        $value = $_SERVER[$key] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
