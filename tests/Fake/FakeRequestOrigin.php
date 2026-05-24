<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Http\RequestOriginInterface;
use Override;

/**
 * Constructor-scripted `RequestOriginInterface` for tests.
 *
 * Defaults are all `null`, so a test that doesn't care about origin
 * signals — combined with a `FakeAllowedOrigin(null)` — gets the gate's
 * short-circuit path without any setup. Origin-aware tests override the
 * relevant fields per case.
 */
final readonly class FakeRequestOrigin implements RequestOriginInterface
{
    public function __construct(
        private string|null $fetchSite = null,
        private string|null $origin = null,
        private string|null $referer = null,
    ) {
    }

    #[Override]
    public function fetchSite(): string|null
    {
        return $this->fetchSite;
    }

    #[Override]
    public function origin(): string|null
    {
        return $this->origin;
    }

    #[Override]
    public function referer(): string|null
    {
        return $this->referer;
    }
}
