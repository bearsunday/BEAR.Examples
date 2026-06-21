<?php

declare(strict_types=1);

namespace BEAR\Kata;

use BEAR\Resource\ResourceInterface;
use BEAR\Kata\Auth\UserInterface;
use BEAR\Kata\Auth\Visitor;
use BEAR\Kata\Fake\FakeUserModule;
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
        $injector = Injector::getOverrideInstance('html-test-hal-api-app', new FakeUserModule(new Visitor()));
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    protected function resourceWithUser(UserInterface $user): ResourceInterface
    {
        $injector = Injector::getOverrideInstance('html-test-hal-api-app', new FakeUserModule($user));

        return $injector->getInstance(ResourceInterface::class);
    }
}
