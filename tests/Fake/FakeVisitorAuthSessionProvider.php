<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake;

use BEAR\Kata\Auth\AuthSessionInterface;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<AuthSessionInterface> */
final class FakeVisitorAuthSessionProvider implements ProviderInterface
{
    public function get(): AuthSessionInterface
    {
        return new FakeAuthSession();
    }
}
