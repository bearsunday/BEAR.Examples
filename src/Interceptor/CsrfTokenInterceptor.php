<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Auth\CsrfTokenInterface;
use MyVendor\Cms\Exception\ForbiddenException;
use MyVendor\Cms\Http\RequestBodyTokenInterface;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

/**
 * Synchroniser-token gate for destructive Page/Admin POSTs.
 *
 * Stacks on top of `SameOriginInterceptor` — that one confirms the
 * request came from our origin; this one confirms the form was
 * actually rendered by us (the embedded `_csrf_token` matches the
 * one stored in session). Together they cover the two halves of
 * classic CSRF: same-origin defends most cookie-driven attacks
 * cheaply, the synchroniser token adds defence in depth for the
 * operations where a same-origin compromise (XSS on a sibling
 * subdomain, sloppy `SameSite`) would do the most damage.
 *
 * Failure modes:
 *
 *  - No `_csrf_token` field on the request → `ForbiddenException` (403).
 *  - Submitted value doesn't match the stored token → `ForbiddenException`.
 *  - Stored token is missing (fresh session, post-logout) → `ForbiddenException`.
 *
 * Both branches resolve to the same exception because telling the
 * client *why* they failed leaks structure. The framework's default
 * 4xx pipeline turns the exception into the HTTP response.
 *
 * The interceptor reads the submitted token from `$_POST` via
 * `RequestBodyTokenInterface`, not from method arguments — annotated
 * methods stay focused on their domain inputs and don't need a
 * `_csrf_token` parameter polluting their signatures.
 */
final readonly class CsrfTokenInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfTokenInterface $csrf,
        private RequestBodyTokenInterface $body,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $submitted = $this->body->submitted();
        if ($submitted === null) {
            throw new ForbiddenException('CSRF token missing.');
        }

        if (! $this->csrf->verify($submitted)) {
            throw new ForbiddenException('CSRF token invalid.');
        }

        return $invocation->proceed();
    }
}
