<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

use function is_string;

/** @SuppressWarnings("PHPMD.Superglobals") Header adapter boundary. */
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
