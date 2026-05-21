<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Auth\AuthSessionInterface;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<AuthSessionInterface> */
final class FakeVisitorAuthSessionProvider implements ProviderInterface
{
    public function get(): AuthSessionInterface
    {
        return new FakeAuthSession();
    }
}
