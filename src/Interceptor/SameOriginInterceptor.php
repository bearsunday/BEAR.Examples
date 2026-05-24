<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use BEAR\Resource\Exception\BadRequestException;
use MyVendor\Cms\Exception\ForbiddenException;
use MyVendor\Cms\Http\AllowedOriginInterface;
use MyVendor\Cms\Http\RequestOriginInterface;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function array_intersect_key;
use function array_key_exists;
use function in_array;
use function parse_url;
use function sprintf;
use function strtolower;

/**
 * Same-origin policy gate for browser-driven unsafe HTTP verbs.
 *
 * Applied via `#[SameOrigin]` (see `MyVendor\Cms\Attribute\SameOrigin`).
 * Resources annotated with that attribute have their invocation gated by
 * three signals, in order of precedence:
 *
 *  1. `Sec-Fetch-Site` (Fetch Metadata) — primary. JS cannot forge it
 *     (it's a forbidden request header), so a `same-origin` value is
 *     authoritative. Anything else (`cross-site`, `same-site`, `none`,
 *     or an unknown literal) is rejected outright.
 *  2. `Origin` — fallback for clients that don't emit `Sec-Fetch-Site`.
 *     Compared as a canonical origin (scheme + host lowercased, default
 *     ports collapsed) against the configured allowed origin.
 *  3. `Referer` — last-resort fallback. Same canonical comparison after
 *     extracting the URL's origin component.
 *
 * Failure modes:
 *
 *  - Mismatched / explicitly cross-site → `ForbiddenException` (403).
 *  - Malformed `Origin` / `Referer` (parse failure, opaque `null` origin,
 *    path / query / userinfo present on `Origin`) → `BadRequestException`
 *    (400). Treated as a client-shape problem, not a policy decision.
 *  - All three signals absent → `ForbiddenException` (403, fail-closed).
 *
 * The gate is intentionally bypassed when `AllowedOriginInterface::value()`
 * returns `null` — see that interface's docblock for the rationale and
 * the production fail-closed gap.
 *
 * **Scope reminder.** This interceptor checks request-side origin signals
 * only. The complementary protections — `SameSite=Lax` / `Secure` /
 * `HttpOnly` flags on the session cookie — live on the session cookie
 * issuer, not here.
 */
final readonly class SameOriginInterceptor implements MethodInterceptor
{
    private const array UNSAFE_FETCH_SITES = ['cross-site', 'same-site', 'none'];
    private const array DEFAULT_PORTS = ['http' => 80, 'https' => 443];

    public function __construct(
        private RequestOriginInterface $request,
        private AllowedOriginInterface $allowedOrigin,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $allowed = $this->allowedOrigin->value();
        if ($allowed === null) {
            return $invocation->proceed();
        }

        $allowedCanonical = $this->canonicaliseOrigin($allowed);
        if ($allowedCanonical === null) {
            // Misconfiguration surface — a malformed allowed-origin value
            // should fail closed rather than silently allow.
            throw new ForbiddenException(
                'Same-origin policy: configured allowed origin is malformed.',
            );
        }

        $fetchSite = $this->request->fetchSite();
        if ($fetchSite !== null) {
            return $this->checkFetchSite($invocation, $fetchSite);
        }

        return $this->checkOriginOrReferer($invocation, $allowedCanonical);
    }

    /** @param MethodInvocation<object> $invocation */
    private function checkFetchSite(MethodInvocation $invocation, string $fetchSite): mixed
    {
        if ($fetchSite === 'same-origin') {
            return $invocation->proceed();
        }

        // Unknown literals are treated as cross-site rather than falling
        // through to Origin/Referer — the header is forbidden so a
        // surprising value is more likely an attack than a new browser
        // value we should trust.
        if (in_array($fetchSite, self::UNSAFE_FETCH_SITES, true)) {
            throw new ForbiddenException(
                sprintf('Same-origin policy: Sec-Fetch-Site: %s.', $fetchSite),
            );
        }

        throw new ForbiddenException(
            sprintf('Same-origin policy: unknown Sec-Fetch-Site value: %s.', $fetchSite),
        );
    }

    /** @param MethodInvocation<object> $invocation */
    private function checkOriginOrReferer(MethodInvocation $invocation, string $allowedCanonical): mixed
    {
        $origin = $this->request->origin();
        if ($origin !== null) {
            $originCanonical = $this->canonicaliseOrigin($origin);
            if ($originCanonical === null) {
                throw new BadRequestException(
                    sprintf('Same-origin policy: malformed Origin header: %s.', $origin),
                );
            }

            if ($originCanonical === $allowedCanonical) {
                return $invocation->proceed();
            }

            throw new ForbiddenException(
                sprintf('Same-origin policy: cross-origin Origin: %s.', $origin),
            );
        }

        $referer = $this->request->referer();
        if ($referer !== null) {
            $refererCanonical = $this->canonicaliseRefererOrigin($referer);
            if ($refererCanonical === null) {
                throw new BadRequestException(
                    sprintf('Same-origin policy: malformed Referer header: %s.', $referer),
                );
            }

            if ($refererCanonical === $allowedCanonical) {
                return $invocation->proceed();
            }

            throw new ForbiddenException(
                sprintf('Same-origin policy: cross-origin Referer: %s.', $referer),
            );
        }

        throw new ForbiddenException(
            'Same-origin policy: no Sec-Fetch-Site / Origin / Referer header.',
        );
    }

    /**
     * Canonical origin form: lowercase `scheme://host[:port]`, with
     * scheme default ports collapsed. Returns `null` for malformed
     * inputs or `Origin: null` (the unique opaque origin spec value),
     * which the caller surfaces as 400 / 403 depending on side.
     *
     * Origin headers are spec'd to be just `scheme://host[:port]`; if
     * path / query / fragment / userinfo turn up, treat it as malformed.
     */
    /** URL components that, if present, mean the value isn't a bare origin. */
    private const array NON_ORIGIN_PARTS = ['user' => 0, 'pass' => 0, 'query' => 0, 'fragment' => 0];

    private function canonicaliseOrigin(string $value): string|null
    {
        if ($value === 'null') {
            return null;
        }

        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        // Origin = scheme://host[:port] only.
        if (array_intersect_key($parts, self::NON_ORIGIN_PARTS) !== []) {
            return null;
        }

        // parse_url accepts a single trailing slash on path; tolerate
        // that one form but reject anything more substantive.
        $path = array_key_exists('path', $parts) ? $parts['path'] : '';
        if ($path !== '' && $path !== '/') {
            return null;
        }

        return $this->renderCanonical($parts['scheme'], $parts['host'], $parts['port'] ?? null);
    }

    /**
     * `Referer` is a full URL — extract its origin component only.
     * `null` means malformed (parse_url failed or scheme/host missing).
     */
    private function canonicaliseRefererOrigin(string $value): string|null
    {
        $parts = parse_url($value);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        return $this->renderCanonical($parts['scheme'], $parts['host'], $parts['port'] ?? null);
    }

    private function renderCanonical(string $scheme, string $host, int|null $port): string
    {
        $scheme = strtolower($scheme);
        $host = strtolower($host);

        if ($port !== null && isset(self::DEFAULT_PORTS[$scheme]) && self::DEFAULT_PORTS[$scheme] === $port) {
            $port = null;
        }

        return $port === null
            ? sprintf('%s://%s', $scheme, $host)
            : sprintf('%s://%s:%d', $scheme, $host, $port);
    }
}
