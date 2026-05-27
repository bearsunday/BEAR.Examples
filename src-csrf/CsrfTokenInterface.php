<?php

declare(strict_types=1);

namespace Ray\Csrf;

interface CsrfTokenInterface
{
    /** Returns the session's token, generating one on first call. */
    public function issue(): string;

    /** Constant-time compare. Returns `false` when no token has been issued yet. */
    public function verify(string $candidate): bool;

    /** Discards the stored token; consumers call this on logout. */
    public function clear(): void;
}
