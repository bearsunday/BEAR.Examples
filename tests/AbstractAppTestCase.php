<?php

declare(strict_types=1);

namespace MyVendor\Cms;

use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * Shared setUp for App resource tests.
 *
 * Context `test-hal-api-app` loads TestModule which installs FakeModule,
 * so tests run against FakeSqlQuery and do not touch any real database.
 */
abstract class AbstractAppTestCase extends TestCase
{
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getInstance('test-hal-api-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }
}
