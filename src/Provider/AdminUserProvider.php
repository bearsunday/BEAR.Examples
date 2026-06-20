<?php

declare(strict_types=1);

namespace BEAR\Examples\Provider;

use BEAR\Examples\Auth\AdminUserInterface;
use BEAR\Examples\Auth\UserInterface;
use BEAR\Examples\Exception\UnauthenticatedException;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<AdminUserInterface> */
final readonly class AdminUserProvider implements ProviderInterface
{
    public function __construct(
        private UserInterface $user,
    ) {
    }

    public function get(): AdminUserInterface
    {
        if (! $this->user instanceof AdminUserInterface) {
            throw new UnauthenticatedException();
        }

        return $this->user;
    }
}
