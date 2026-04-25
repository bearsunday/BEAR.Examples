<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Resource\Module\JsonSchemaModule;
use Koriym\EnvJson\EnvJson;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\MediaQuery\DbQueryConfig;
use Ray\MediaQuery\MediaQueryBaseModule;
use Ray\MediaQuery\MediaQueryDbModule;
use Ray\MediaQuery\Queries;

use function array_merge;
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

        // Scan both Query (Read) and Command (Write) interface directories.
        $queries = Queries::fromClasses(array_merge(
            Queries::fromDir($this->appMeta->appDir . '/src/Query')->classes,
            Queries::fromDir($this->appMeta->appDir . '/src/Command')->classes,
        ));
        $this->install(new MediaQueryBaseModule($queries));
        $this->install(new MediaQueryDbModule(new DbQueryConfig($this->appMeta->appDir . '/var/db/sql')));

        // Validate response bodies (and optionally request params) against JSON Schemas.
        $this->install(new JsonSchemaModule(
            $this->appMeta->appDir . '/var/json_schema',
            $this->appMeta->appDir . '/var/json_validate',
        ));
    }
}
