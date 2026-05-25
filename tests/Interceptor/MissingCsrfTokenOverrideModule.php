<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Fake\FakeRequestBodyToken;
use MyVendor\Cms\Http\RequestBodyTokenInterface;
use Override;
use Ray\Di\AbstractModule;

/**
 * Test-only DI override that scripts `RequestBodyTokenInterface` to
 * report no submitted token — the rest of the test suite relies on
 * the default `FakeRequestBodyToken` returning a value that matches
 * `FakeCsrfToken`, so the gate normally short-circuits.
 *
 * Scoped to `CsrfTokenWiringTest` for the same locality reason as
 * `CrossSiteOriginOverrideModule`: keep the cross-cutting override
 * out of unrelated tests. Constructor accepts an inner module so the
 * test can chain `FakeUserModule` for admin session setup.
 */
final class MissingCsrfTokenOverrideModule extends AbstractModule
{
    public function __construct(AbstractModule|null $module = null)
    {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(RequestBodyTokenInterface::class)
            ->toInstance(new FakeRequestBodyToken(null));
    }
}
