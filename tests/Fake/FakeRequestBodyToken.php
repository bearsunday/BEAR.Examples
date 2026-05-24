<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Http\RequestBodyTokenInterface;
use Override;

/**
 * Constructor-scripted `RequestBodyTokenInterface` for tests.
 *
 * Defaults to `FakeCsrfToken::DEFAULT_TOKEN` so a test that doesn't
 * touch CSRF concerns sees the interceptor short-circuit through
 * (token matches, gate proceeds). Tests that exercise the gate
 * construct this with a mismatching value or `null`.
 */
final readonly class FakeRequestBodyToken implements RequestBodyTokenInterface
{
    public function __construct(private string|null $submitted = FakeCsrfToken::DEFAULT_TOKEN)
    {
    }

    #[Override]
    public function submitted(): string|null
    {
        return $this->submitted;
    }
}
