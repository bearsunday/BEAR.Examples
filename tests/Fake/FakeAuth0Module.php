<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Auth\AuthInterface;
use Ray\Di\AbstractModule;

final class FakeAuth0Module extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(AuthInterface::class)->toInstance(new FakeAuthProvider(
            userId: 'auth0|editor-1',
            email: 'evelyn.moore1@example.com',
            name: 'Evelyn Moore',
            provider: 'auth0',
        ));
    }
}
