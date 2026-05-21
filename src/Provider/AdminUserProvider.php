<?php

declare(strict_types=1);

namespace MyVendor\Cms\Provider;

use MyVendor\Cms\Auth\AdminUserInterface;
use MyVendor\Cms\Auth\UserInterface;
use MyVendor\Cms\Exception\UnauthenticatedException;
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
