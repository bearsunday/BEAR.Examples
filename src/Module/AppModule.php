<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Resource\Module\JsonSchemaModule;
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

        // Read (QueryInterface) and Write (CommandInterface) live side-by-side
        // in src/Query so MediaQuerySqlModule scans a single directory.
        $this->install(new MediaQuerySqlModule(
            interfaceDir: $this->appMeta->appDir . '/src/Query',
            sqlDir: $this->appMeta->appDir . '/var/db/sql',
        ));

        // Validate response bodies (and optionally request params) against JSON Schemas.
        $this->install(new JsonSchemaModule(
            $this->appMeta->appDir . '/var/json_schema',
            $this->appMeta->appDir . '/var/json_validate',
        ));
    }
}
