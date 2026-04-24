<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use MyVendor\Cms\Fake\FakeSqlQuery;
use Ray\Di\AbstractModule;
use Ray\Di\Scope;
use Ray\MediaQuery\SqlQueryInterface;

/**
 * Swaps Ray.MediaQuery's SqlQueryInterface with an in-memory FakeSqlQuery.
 *
 * Use context `fake-hal-api-app` (or `cli-fake-hal-api-app`) to run the app
 * without a real database, backed by var/fake/*.json.
 */
final class FakeModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)->to(FakeSqlQuery::class)->in(Scope::SINGLETON);
    }
}
