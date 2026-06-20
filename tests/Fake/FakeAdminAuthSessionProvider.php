<?php

declare(strict_types=1);

namespace BEAR\Examples\Fake;

use BEAR\Examples\Auth\AdminUser;
use BEAR\Examples\Auth\AuthSessionInterface;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<AuthSessionInterface> */
final class FakeAdminAuthSessionProvider implements ProviderInterface
{
    public function get(): AuthSessionInterface
    {
        return new FakeAuthSession(new AdminUser(
            id: 'fake-admin-1',
            email: 'evelyn.moore1@example.com',
            name: 'Evelyn Moore',
            authorId: 1,
        ));
    }
}
