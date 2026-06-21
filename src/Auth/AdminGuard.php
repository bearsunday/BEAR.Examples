<?php

declare(strict_types=1);

namespace BEAR\Kata\Auth;

use BEAR\Kata\Exception\UnauthenticatedException;

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
