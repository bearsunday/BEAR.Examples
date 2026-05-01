<?php

declare(strict_types=1);

namespace MyVendor\Cms;

use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * Shared setUp for Page resource tests.
 *
 * Context `html-test-hal-api-app` composes TestModule and HtmlModule,
 * so tests run against FakeSqlQuery and render Page resources as HTML.
 */
abstract class AbstractPageTestCase extends TestCase
{
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getInstance('html-test-hal-api-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }
}
