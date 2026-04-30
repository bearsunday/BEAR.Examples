<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Package\AbstractAppModule;
use BEAR\Package\PackageModule;
use BEAR\Resource\Module\JsonSchemaModule;
use Koriym\EnvJson\EnvJson;
use League\CommonMark\CommonMarkConverter;
use League\OAuth2\Client\Provider\Google;
use MyVendor\Cms\Auth\AuthInterface;
use MyVendor\Cms\Auth\GoogleAuthProvider;
use MyVendor\Cms\Factory\ArticleFactory;
use MyVendor\Cms\Provider\GoogleProvider;
use MyVendor\Cms\Service\CommonMarkRenderer;
use MyVendor\Cms\Service\MarkdownRendererInterface;
use Ray\AuraSqlModule\AuraSqlModule;
use Ray\Di\Scope;
use Ray\MediaQuery\MediaQuerySqlModule;

use function dirname;
use function getenv;

/**
 * Production / CLI bindings: real DB via AuraSqlModule + Ray.MediaQuery,
 * JSON Schema validation, and Google OAuth.
 *
 * Loaded by contexts `hal-api-app` and `cli-hal-api-app`. The fake/test
 * contexts (`fake-hal-api-app`, `test-hal-api-app`) install this and then
 * override individual bindings via FakeModule / TestModule.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects") composition root by design
 */
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
            $this->appMeta->appDir . '/src/Query',
            $this->appMeta->appDir . '/var/db/sql',
        ));

        // Validate response bodies (and optionally request params) against JSON Schemas.
        $this->install(new JsonSchemaModule(
            $this->appMeta->appDir . '/var/json_schema',
            $this->appMeta->appDir . '/var/json_validate',
        ));

        // Domain-layer services (e.g. injected into Entity via FetchInjectionFactory).
        // CommonMarkConverter has only defaultable constructor args, so a single
        // shared instance is enough; binding it to a Provider would be over-engineering.
        $this->bind(CommonMarkConverter::class)->toInstance(new CommonMarkConverter());
        $this->bind(MarkdownRendererInterface::class)->to(CommonMarkRenderer::class)->in(Scope::SINGLETON);
        // Explicit untargeted binding so Ray.Compiler (prod-app) can resolve the
        // factory referenced by #[DbQuery(factory: ArticleFactory::class)].
        $this->bind(ArticleFactory::class)->in(Scope::SINGLETON);

        // Authentication: Google OAuth in production. FakeAuthProvider in test/fake.
        $this->bind(Google::class)->toProvider(GoogleProvider::class)->in(Scope::SINGLETON);
        $this->bind(AuthInterface::class)->to(GoogleAuthProvider::class)->in(Scope::SINGLETON);
    }
}
