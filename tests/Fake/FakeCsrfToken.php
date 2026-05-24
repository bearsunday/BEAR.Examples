<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Auth\CsrfTokenInterface;
use Override;

use function hash_equals;

/**
 * Constructor-scripted `CsrfTokenInterface` for tests.
 *
 * Defaults to a fixed token that matches `FakeRequestBodyToken`'s
 * default submitted value, so a test that doesn't touch CSRF
 * concerns sees the interceptor short-circuit through. Tests that
 * exercise the gate construct this with a known token and the body
 * fake with a mismatching value (or a `null`).
 *
 * Using a fixed token rather than "always-true verify()" preserves
 * the verification path under test wiring — a passing test means
 * `hash_equals` actually returned `true`, not that verification was
 * stubbed away.
 */
final readonly class FakeCsrfToken implements CsrfTokenInterface
{
    public const string DEFAULT_TOKEN = 'fake-csrf-token';

    public function __construct(private string $token = self::DEFAULT_TOKEN)
    {
    }

    #[Override]
    public function issue(): string
    {
        return $this->token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        return $candidate !== '' && hash_equals($this->token, $candidate);
    }
}
