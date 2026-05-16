<?php

declare(strict_types=1);

namespace MyVendor\Cms\Provider;

use MyVendor\Cms\Auth\AuthSessionInterface;
use MyVendor\Cms\Auth\UserInterface;
use Ray\Di\ProviderInterface;

/** @implements ProviderInterface<UserInterface> */
final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private AuthSessionInterface $session,
    ) {
    }

    public function get(): UserInterface
    {
        return $this->session->currentUser();
    }
}
