<?php

declare(strict_types=1);

namespace BEAR\Examples\Provider;

use BEAR\Examples\Auth\AuthSessionInterface;
use BEAR\Examples\Auth\UserInterface;
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
