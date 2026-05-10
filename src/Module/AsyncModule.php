<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Async\Module\AsyncParallelModule;
use BEAR\Package\AbstractAppModule;
use MyVendor\Cms\Exception\AsyncWorkerContextNotSetException;

/**
 * Enables parallel execution of existing #[Embed] resources.
 *
 * MyVendor\Cms\Injector supplies the worker context explicitly so worker
 * threads don't recursively create another parallel runtime.
 */
final class AsyncModule extends AbstractAppModule
{
    private string $workerContext = '';

    public function setWorkerContext(string $workerContext): void
    {
        $this->workerContext = $workerContext;
    }

    protected function configure(): void
    {
        if ($this->workerContext === '') {
            throw new AsyncWorkerContextNotSetException();
        }

        $this->install(new AsyncParallelModule(
            namespace: $this->appMeta->name,
            context: $this->workerContext,
            appDir: $this->appMeta->appDir,
            poolSize: 4,
        ));
    }
}
