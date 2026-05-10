<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use BEAR\Async\Module\AsyncParallelModule;
use BEAR\Package\AbstractAppModule;

use function basename;
use function str_starts_with;
use function substr;

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
        $this->install(new AsyncParallelModule(
            namespace: $this->appMeta->name,
            context: self::workerContextFromTmpDir($this->appMeta->tmpDir),
            appDir: $this->appMeta->appDir,
            poolSize: 4,
        ));
    }

    public static function workerContextFromTmpDir(string $tmpDir): string
    {
        // Convention-dependent: BEAR.AppMeta\Meta writes tmpDir as var/tmp/{context}.
        $context = basename($tmpDir);

        return str_starts_with($context, 'async-') ? substr($context, 6) : $context;
    }
}
