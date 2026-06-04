<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Package\Context\ProdModule as PackageProdModule;
use BEAR\QueryRepository\StorageRedisDsnModule;
use Override;
use Ray\Di\AbstractModule;

use function getenv;

/**
 * Production context overlay.
 *
 * BEAR.Package's ProdModule installs compiled DI, production error/logging
 * bindings, and local QueryRepository cache storage. This project-level module
 * keeps that default path, then optionally swaps the QueryRepository storage
 * to Redis when CMS_REDIS_DSN is configured.
 */
final class ProdModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->install(new PackageProdModule());

        $redisDsn = getenv('CMS_REDIS_DSN');
        if ($redisDsn === false || $redisDsn === '') {
            return;
        }

        $this->install(new StorageRedisDsnModule((string) $redisDsn));
    }
}
