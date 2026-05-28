<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

interface CsrfTokenInterface
{
    /** Returns the session's token, generating one on first call. */
    public function issue(): string;

    /** Constant-time compare. Returns `false` when no token has been issued yet. */
    public function verify(string $candidate): bool;

    /** Discards the stored token; called by `AuthSessionInterface::logout()`. */
    public function clear(): void;
}
