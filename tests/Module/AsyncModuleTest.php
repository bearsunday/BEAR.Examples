<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AsyncModuleTest extends TestCase
{
    #[DataProvider('workerContextProvider')]
    public function testWorkerContextFromTmpDir(string $tmpDir, string $expected): void
    {
        $this->assertSame($expected, AsyncModule::workerContextFromTmpDir($tmpDir));
    }

    /** @return iterable<string, array{string, string}> */
    public static function workerContextProvider(): iterable
    {
        yield 'prod async context' => ['/app/var/tmp/async-hal-api-app', 'hal-api-app'];
        yield 'test async context' => ['/app/var/tmp/async-test-hal-api-app', 'test-hal-api-app'];
        yield 'demo async context' => ['/app/var/tmp/async-slow-fake-hal-api-app', 'slow-fake-hal-api-app'];
        yield 'plain context' => ['/app/var/tmp/test-hal-api-app', 'test-hal-api-app'];
    }
}
