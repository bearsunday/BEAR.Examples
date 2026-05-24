<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Fake\FakeAllowedOrigin;
use MyVendor\Cms\Fake\FakeRequestOrigin;
use MyVendor\Cms\Http\AllowedOriginInterface;
use MyVendor\Cms\Http\RequestOriginInterface;
use Override;
use Ray\Di\AbstractModule;

/**
 * Test-only DI module that forces `SameOriginInterceptor` to actually
 * evaluate rather than the usual short-circuit
 * (`FakeAllowedOrigin(null)` + `FakeRequestOrigin()` all-null) the
 * rest of the test suite relies on.
 *
 * Scripts a cross-site `Sec-Fetch-Site` and a non-null allowed origin
 * so the gate trips. Scoped to `SameOriginWiringTest` to keep the
 * cross-site script out of unrelated tests; lives in its own file
 * because PSR-1 forbids multiple classes per file.
 *
 * Accepts an optional inner module so callers can chain another
 * override (e.g. `FakeUserModule`) without two layers of nesting at
 * the call site. The constructor is declared explicitly — even
 * though `Ray\Di\AbstractModule::__construct(?self $module = null)`
 * already accepts the same argument — to make the test's
 * composition obvious to readers (per CodeRabbit review feedback).
 */
final class CrossSiteOriginOverrideModule extends AbstractModule
{
    public function __construct(AbstractModule|null $module = null)
    {
        parent::__construct($module);
    }

    #[Override]
    protected function configure(): void
    {
        $this->bind(RequestOriginInterface::class)
            ->toInstance(new FakeRequestOrigin(fetchSite: 'cross-site'));
        $this->bind(AllowedOriginInterface::class)
            ->toInstance(new FakeAllowedOrigin('https://cms.example.com'));
    }
}
