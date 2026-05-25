<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Attribute\CsrfToken;
use MyVendor\Cms\Attribute\SameOrigin;
use MyVendor\Cms\Auth\CsrfTokenInterface;
use MyVendor\Cms\Auth\SessionCsrfToken;
use MyVendor\Cms\Http\AllowedOriginInterface;
use MyVendor\Cms\Http\EnvAllowedOrigin;
use MyVendor\Cms\Http\RequestBodyTokenInterface;
use MyVendor\Cms\Http\RequestOriginInterface;
use MyVendor\Cms\Http\ServerRequestBodyToken;
use MyVendor\Cms\Http\ServerRequestOrigin;
use MyVendor\Cms\Interceptor\CsrfTokenInterceptor;
use MyVendor\Cms\Interceptor\SameOriginInterceptor;
use Override;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Cross-site request defence wiring.
 *
 * Two attributes live here so admins reading the source can see the
 * full CSRF surface at a glance:
 *
 *  - `#[SameOrigin]` — same-origin check via
 *    `Sec-Fetch-Site` / `Origin` / `Referer`, applied to every
 *    Page/Admin unsafe POST.
 *  - `#[CsrfToken]` — synchroniser-token check against a per-session
 *    secret, applied to destructive / session-changing POSTs (publish,
 *    delete, logout, and `Article::onPost` because its `status=published`
 *    parameter is an alternative publish path).
 *
 * The two stack: `#[SameOrigin]` defends most cookie-driven CSRF
 * cheaply, `#[CsrfToken]` adds defence in depth for the operations
 * where a same-origin compromise would do the most damage. Either
 * failing rejects the request before the resource method runs.
 *
 * Test / fake / CLI contexts override these bindings to fakes; see
 * `tests/Fake/FakeRequestOrigin.php`, `tests/Fake/FakeAllowedOrigin.php`,
 * `tests/Fake/FakeCsrfToken.php`, and `tests/Fake/FakeRequestBodyToken.php`.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects") composition root by design — two CSRF layers wire here, the class names add up
 */
final class CsrfModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        // Same-origin layer.
        $this->bind(RequestOriginInterface::class)->to(ServerRequestOrigin::class);
        $this->bind(AllowedOriginInterface::class)->to(EnvAllowedOrigin::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(SameOrigin::class),
            [SameOriginInterceptor::class],
        );

        // Synchroniser-token layer. SessionCsrfToken is bound singleton
        // so every request inside one PHP request lifecycle sees the
        // same token instance; the underlying storage is `$_SESSION`,
        // which is what actually persists across requests.
        $this->bind(CsrfTokenInterface::class)->to(SessionCsrfToken::class)->in(Scope::SINGLETON);
        $this->bind(RequestBodyTokenInterface::class)->to(ServerRequestBodyToken::class);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(CsrfToken::class),
            [CsrfTokenInterceptor::class],
        );
    }
}
