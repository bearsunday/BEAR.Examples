<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use Koriym\EnvJson\EnvJson;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\MediaQuery\MediaQuerySqlModule;

use function dirname;
use function getenv;

final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        (new EnvJson())->load(dirname(__DIR__, 2));

        $this->install(new PackageModule());

        $dsn = (string) (getenv('DB_DSN') ?: 'mysql:host=127.0.0.1;dbname=bear_cms;charset=utf8mb4');
        $user = (string) (getenv('DB_USER') ?: 'root');
        $password = (string) getenv('DB_PASSWORD');
        $this->install(new AuraSqlModule($dsn, $user, $password));

        $this->install(new MediaQuerySqlModule(
            interfaceDir: $this->appMeta->appDir . '/src/Query',
            sqlDir: $this->appMeta->appDir . '/var/db/sql',
        ));
    }
}
