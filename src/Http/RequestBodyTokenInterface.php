<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

/**
 * Reads the CSRF token submitted by the client.
 *
 * Production-bound by `ServerRequestBodyToken` (`$_POST['_csrf_token']`
 * wrapper); tests swap in `FakeRequestBodyToken`. The split mirrors
 * `RequestOriginInterface` / `ServerRequestOrigin` — request-state
 * adapters are interface-backed so interceptors stay unit-testable.
 *
 * Only `$_POST` is read. AJAX flows that would use an `X-CSRF-Token`
 * header are out of scope today (no JS-driven write surface yet) —
 * if that lands, this interface gains an `'header'` source or the
 * name widens to `SubmittedCsrfTokenInterface`.
 */
interface RequestBodyTokenInterface
{
    /**
     * Submitted token value, or `null` when the form field is absent.
     * An empty string is also returned as `null` so callers don't
     * have to repeat the empty-check.
     */
    public function submitted(): string|null;
}
