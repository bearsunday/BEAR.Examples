<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use Ray\Di\AbstractModule;

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
    }
}
