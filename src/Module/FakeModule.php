<?php

declare(strict_types=1);

namespace BEAR\Examples\Module;

use BEAR\Examples\Auth\AuthInterface;
use BEAR\Examples\Auth\AuthSessionInterface;
use BEAR\Examples\Fake\FakeAdminAuthSessionProvider;
use BEAR\Examples\Fake\FakeAuthProvider;
use BEAR\Examples\Fake\FakeCsrfToken;
use BEAR\Examples\Fake\FakeRequestBodyToken;
use BEAR\Examples\Fake\FakeRequestOrigin;
use BEAR\Examples\Fake\FakeSqlQuery;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Http\AllowedOrigin;
use Ray\Csrf\Http\RequestBodyTokenInterface;
use Ray\Csrf\Http\RequestOriginInterface;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\SqlQueryInterface;

/**
 * Swaps real-infra interfaces with deterministic in-memory fakes.
 *
 * Use context `fake-hal-api-app` (or `cli-fake-hal-api-app`) to run the app
 * without a real database / OAuth provider, backed by var/fake/*.json and
 * a fixed user identity.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects") composition root
 */
final class FakeModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)->to(FakeSqlQuery::class)->in(Scope::SINGLETON);
        $this->bind(AuthInterface::class)->to(FakeAuthProvider::class)->in(Scope::SINGLETON);
        $this->bind(AuthSessionInterface::class)->toProvider(FakeAdminAuthSessionProvider::class)->in(Scope::SINGLETON);

        // Force the CSRF gates off — fake / test runs don't drive HTTP, so
        // there's no Sec-Fetch-Site / Origin / Referer / _csrf_token to
        // script. Tests that exercise the gates rebind AllowedOrigin (and
        // the relevant header / body fake) via overrideModule().
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin(null));
        $this->bind(RequestOriginInterface::class)->to(FakeRequestOrigin::class)->in(Scope::SINGLETON);
        $this->bind(CsrfTokenInterface::class)->to(FakeCsrfToken::class)->in(Scope::SINGLETON);
        $this->bind(RequestBodyTokenInterface::class)->to(FakeRequestBodyToken::class)->in(Scope::SINGLETON);
    }
}
