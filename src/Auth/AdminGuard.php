<?php

declare(strict_types=1);

namespace BEAR\Examples\Auth;

use BEAR\Examples\Exception\UnauthenticatedException;

final readonly class AdminGuard
{
    public function __construct(
        private UserInterface $user,
    ) {
    }

    public function user(): AdminUserInterface
    {
        if (! $this->user instanceof AdminUserInterface) {
            throw new UnauthenticatedException();
        }

        return $this->user;
    }
}
