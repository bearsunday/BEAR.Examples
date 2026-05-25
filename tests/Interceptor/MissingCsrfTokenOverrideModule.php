<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Fake\FakeRequestBodyToken;
use MyVendor\Cms\Fake\FakeRequestOrigin;
use MyVendor\Cms\Http\AllowedOrigin;
use MyVendor\Cms\Http\RequestBodyTokenInterface;
use MyVendor\Cms\Http\RequestOriginInterface;
use Override;
use Ray\Di\AbstractModule;

/**
 * Forces `CsrfTokenInterceptor` to actually evaluate (non-null
 * AllowedOrigin) and scripts a missing submitted token to trip the
 * gate. Also scripts `Sec-Fetch-Site: same-origin` so the stacked
 * `SameOriginInterceptor` proceeds and lets the CSRF gate be the
 * first to reject. Scoped to `CsrfTokenWiringTest`.
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
        $this->bind(AllowedOrigin::class)
            ->toInstance(new AllowedOrigin('https://cms.example.com'));
        $this->bind(RequestOriginInterface::class)
            ->toInstance(new FakeRequestOrigin(fetchSite: 'same-origin'));
        $this->bind(RequestBodyTokenInterface::class)
            ->toInstance(new FakeRequestBodyToken(null));
    }
}
