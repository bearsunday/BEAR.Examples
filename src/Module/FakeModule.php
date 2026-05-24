<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use MyVendor\Cms\Auth\AuthInterface;
use MyVendor\Cms\Auth\AuthSessionInterface;
use MyVendor\Cms\Fake\FakeAdminAuthSessionProvider;
use MyVendor\Cms\Fake\FakeAllowedOrigin;
use MyVendor\Cms\Fake\FakeAuthProvider;
use MyVendor\Cms\Fake\FakeRequestOrigin;
use MyVendor\Cms\Fake\FakeSqlQuery;
use MyVendor\Cms\Http\AllowedOriginInterface;
use MyVendor\Cms\Http\RequestOriginInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\SqlQueryInterface;

/**
 * Swaps real-infra interfaces with deterministic in-memory fakes.
 *
 * Use context `fake-hal-api-app` (or `cli-fake-hal-api-app`) to run the app
 * without a real database / OAuth provider, backed by var/fake/*.json and
 * a fixed user identity.
 */
final class FakeModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)->to(FakeSqlQuery::class)->in(Scope::SINGLETON);
        $this->bind(AuthInterface::class)->to(FakeAuthProvider::class)->in(Scope::SINGLETON);
        $this->bind(AuthSessionInterface::class)->toProvider(FakeAdminAuthSessionProvider::class)->in(Scope::SINGLETON);

        // SameOriginInterceptor short-circuits when allowedOrigin is null,
        // so fake / CLI runs don't need to script HTTP headers. Tests that
        // exercise the gate rebind these via overrideModule().
        $this->bind(RequestOriginInterface::class)->to(FakeRequestOrigin::class)->in(Scope::SINGLETON);
        $this->bind(AllowedOriginInterface::class)->to(FakeAllowedOrigin::class)->in(Scope::SINGLETON);
    }
}
