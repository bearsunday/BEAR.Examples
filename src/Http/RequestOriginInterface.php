<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

/**
 * HTTP-side origin signals used by `SameOriginInterceptor` to recognise
 * cross-site unsafe requests.
 *
 * Production-bound by `ServerRequestOrigin` (`$_SERVER` wrapper); tests and
 * fake contexts swap in `FakeRequestOrigin` to script header values without
 * touching superglobals. The split mirrors the `AuthSessionInterface` /
 * `NativeAuthSession` pattern — request-state adapters are interface-backed
 * so the interceptor stays unit-testable.
 *
 * Each accessor returns the header value verbatim, or `null` when the
 * header is absent. Parsing / canonicalisation lives in the interceptor.
 */
interface RequestOriginInterface
{
    /**
     * `Sec-Fetch-Site` request header (RFC 8941, Fetch Metadata) — `null`
     * on browsers / clients that don't emit it.
     *
     * Expected values: `same-origin`, `same-site`, `cross-site`, `none`.
     * Any other value is treated as untrusted and rejected (defence in
     * depth against header injection).
     */
    public function fetchSite(): string|null;

    /** `Origin` request header — `null` when absent. */
    public function origin(): string|null;

    /** `Referer` request header — `null` when absent. */
    public function referer(): string|null;
}
