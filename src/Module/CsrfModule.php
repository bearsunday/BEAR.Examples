<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Attribute\SameOrigin;
use MyVendor\Cms\Http\AllowedOriginInterface;
use MyVendor\Cms\Http\EnvAllowedOrigin;
use MyVendor\Cms\Http\RequestOriginInterface;
use MyVendor\Cms\Http\ServerRequestOrigin;
use MyVendor\Cms\Interceptor\SameOriginInterceptor;
use Override;
use Ray\Di\AbstractModule;

/**
 * Cross-site request defence wiring.
 *
 * Today binds the request-origin interfaces and registers
 * `SameOriginInterceptor` against `#[SameOrigin]`. The future
 * `#[CsrfToken]` interceptor (PR-B2) will join this module so the two
 * defence layers live in the same wiring file — admins reading the
 * source can see the full CSRF surface at a glance.
 *
 * Test / fake / CLI contexts override these bindings to fakes; see
 * `tests/Fake/FakeRequestOrigin.php` and `tests/Fake/FakeAllowedOrigin.php`.
 */
final class CsrfModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(RequestOriginInterface::class)->to(ServerRequestOrigin::class);
        $this->bind(AllowedOriginInterface::class)->to(EnvAllowedOrigin::class);

        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(SameOrigin::class),
            [SameOriginInterceptor::class],
        );
    }
}
