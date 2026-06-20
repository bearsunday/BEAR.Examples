<?php

declare(strict_types=1);

namespace BEAR\Examples\Example\ImportedCatalog\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new PackageModule());
    }
}
