<?php

declare(strict_types=1);

namespace BEAR\Kata\Provider;

use BEAR\Kata\Auth\AdminUserInterface;
use BEAR\Kata\Auth\UserInterface;
use BEAR\Kata\Exception\UnauthenticatedException;
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
