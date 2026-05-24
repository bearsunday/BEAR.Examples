<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Http\AllowedOriginInterface;
use Override;

/**
 * Constructor-scripted `AllowedOriginInterface` for tests.
 *
 * Default `null` so `FakeModule` / `TestModule` wiring lets the same-origin
 * interceptor short-circuit by default. Tests that actually want to
 * exercise the gate construct this with an explicit allowed origin.
 */
final readonly class FakeAllowedOrigin implements AllowedOriginInterface
{
    public function __construct(private string|null $value = null)
    {
    }

    #[Override]
    public function value(): string|null
    {
        return $this->value;
    }
}
