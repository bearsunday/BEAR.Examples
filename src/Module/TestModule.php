<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use Aura\Sql\ExtendedPdoInterface;
use MyVendor\Cms\Auth\AuthSessionInterface;
use MyVendor\Cms\Fake\FakeExtendedPdoProvider;
use MyVendor\Cms\Fake\FakeVisitorAuthSessionProvider;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;

/**
 * Test-time overrides: installs FakeModule for database-less tests.
 *
 * Context `test-hal-api-app` or `test-app` loads this module.
 */
final class TestModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->install(new FakeModule());
        $this->bind(AuthSessionInterface::class)->toProvider(FakeVisitorAuthSessionProvider::class)->in(Scope::SINGLETON);
        // ArticleRawPdo is exercised in test-hal-api-app; fake-hal-api-app keeps the MediaQuery fake only.
        $this->bind(ExtendedPdoInterface::class)->toProvider(FakeExtendedPdoProvider::class)->in(Scope::SINGLETON);
    }
}
