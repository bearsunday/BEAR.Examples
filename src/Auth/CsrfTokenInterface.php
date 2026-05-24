<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

/**
 * Server-side CSRF token store.
 *
 * Lives next to `AuthSessionInterface` rather than inside it because
 * the responsibilities are different: `AuthSessionInterface` answers
 * "who is logged in / what OAuth state did they start", this answers
 * "what synchroniser token did we hand the browser, and does the one
 * coming back match". Splitting keeps each interface's public surface
 * focused on one concern.
 *
 * The implementation `SessionCsrfToken` is the only place that touches
 * superglobal session state for CSRF — the rest of the application
 * reads through this interface. Tests swap in a `FakeCsrfToken` so
 * unit-level work doesn't need to script a session.
 *
 * Token lifecycle is per-session: `issue()` generates on first call
 * and returns the same value for the lifetime of the session.
 * `NativeAuthSession::logout()` is responsible for clearing the
 * stored token so the next login session gets a fresh one.
 *
 * Per-request rotation is intentionally *not* done — a form rendered
 * at GET time and submitted at POST time would otherwise see two
 * different tokens.
 */
interface CsrfTokenInterface
{
    /**
     * Returns the session's CSRF token. Idempotent — generates and
     * stores once per session, returns the stored value thereafter.
     */
    public function issue(): string;

    /**
     * Constant-time comparison against the stored token. Returns
     * `false` when no token has been issued (defence against blank-
     * stored / blank-submitted equality).
     */
    public function verify(string $candidate): bool;
}
