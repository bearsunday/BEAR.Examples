<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Async\Module\AsyncParallelModule;
use BEAR\Package\AbstractAppModule;

use function assert;
use function basename;
use function preg_replace;

/**
 * Enables parallel execution of existing #[Embed] resources.
 *
 * Worker threads use the same context without the leading `async-` prefix so
 * they don't recursively create another parallel runtime.
 */
final class AsyncModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $context = basename($this->appMeta->tmpDir);
        $workerContext = preg_replace('/^async-/', '', $context);
        assert($workerContext !== null && $workerContext !== '');

        $this->install(new AsyncParallelModule(
            namespace: $this->appMeta->name,
            context: $workerContext,
            appDir: $this->appMeta->appDir,
            poolSize: 4,
        ));
    }
}
