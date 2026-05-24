<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

use function getenv;
use function is_string;

/**
 * Reads `CMS_ALLOWED_ORIGIN` from the process environment.
 *
 * Production deployments set the env var explicitly (e.g.
 * `CMS_ALLOWED_ORIGIN=https://cms.example.com`). When unset or empty,
 * returns `null` and the interceptor short-circuits — adequate for local
 * dev, the CLI, and tests that wire this implementation. See the
 * production fail-closed note on `AllowedOriginInterface`.
 */
final readonly class EnvAllowedOrigin implements AllowedOriginInterface
{
    #[Override]
    public function value(): string|null
    {
        $value = getenv('CMS_ALLOWED_ORIGIN');
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
