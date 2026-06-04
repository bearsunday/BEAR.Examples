<?php

declare(strict_types=1);

namespace MyVendor\Cms\Example;

use BEAR\Package\Module\Import\ImportApp;
use BEAR\Package\Module\ImportAppModule;
use BEAR\Resource\ResourceInterface;
use MyVendor\Cms\Injector;
use Override;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;

final class ImportAppExampleTest extends TestCase
{
    public function testImportsCompanionApplicationUnderCatalogHost(): void
    {
        $injector = Injector::getOverrideInstance('test-hal-api-app', new class extends AbstractModule {
            #[Override]
            protected function configure(): void
            {
                $this->install(new ImportAppModule([
                    new ImportApp('catalog', 'MyVendor\Cms\Example\ImportedCatalog', 'app'),
                ]));
            }
        });

        $resource = $injector->getInstance(ResourceInterface::class);
        $imported = $resource->get('app://catalog/status');

        $this->assertSame(200, $imported->code);
        $this->assertSame('imported-catalog', $imported->body['app']);
        $this->assertSame('BEAR.Sunday ImportAppModule companion example', $imported->body['purpose']);

        $self = $resource->get('app://self/articles');
        $this->assertSame(200, $self->code);
    }
}
