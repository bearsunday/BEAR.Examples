<?php

declare(strict_types=1);

namespace BEAR\Kata;

use BEAR\Resource\ResourceInterface;
use PHPUnit\Framework\TestCase;

/**
 * Shared setUp for App resource tests.
 *
 * Context `test-hal-api-app` loads TestModule which installs FakeModule,
 * so tests run against FakeSqlQuery and do not touch any real database.
 *
 * Why an abstract class here, when src/ avoids inheritance and traits?
 * Tests live in PHPUnit's world, where `extends TestCase` is the standard
 * idiom; one extra layer is consistent with that, not with src/'s
 * BEAR.Sunday DI/AOP model. Sebastian Bergmann's "no state on the test
 * instance" guidance (https://thephp.cc/articles/why-i-manage-test-fixture-differently)
 * is consciously not followed: every test in this project uses the same
 * resource fixture uniformly, so the property + setUp pattern wins on
 * ergonomics over a per-test `$resource = $this->getResource()` call.
 *
 * If a test ever needs a non-`test-hal-api-app` context, write the
 * `Injector::getInstance(...)` call inline in that test's setUp rather
 * than adding a context parameter or a sibling base class — keeping the
 * difference visible at the call site is more valuable than DRY here.
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
