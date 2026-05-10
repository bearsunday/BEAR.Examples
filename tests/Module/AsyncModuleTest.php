<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use MyVendor\Cms\Injector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AsyncModuleTest extends TestCase
{
    #[DataProvider('workerContextProvider')]
    public function testWorkerContextFromAsyncContext(string $context, string $expected): void
    {
        $this->assertSame($expected, Injector::workerContextFromAsyncContext($context));
    }

    /** @return iterable<string, array{string, string}> */
    public static function workerContextProvider(): iterable
    {
        yield 'prod async context' => ['async-hal-api-app', 'hal-api-app'];
        yield 'test async context' => ['async-test-hal-api-app', 'test-hal-api-app'];
        yield 'demo async context' => ['async-slow-fake-hal-api-app', 'slow-fake-hal-api-app'];
        yield 'plain context' => ['test-hal-api-app', 'test-hal-api-app'];
    }
}
