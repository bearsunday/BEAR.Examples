<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use Aura\Sql\ExtendedPdoInterface;
use MyVendor\Cms\Fake\FakeExtendedPdoProvider;
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
        $this->bind(ExtendedPdoInterface::class)->toProvider(FakeExtendedPdoProvider::class)->in(Scope::SINGLETON);
    }
}
