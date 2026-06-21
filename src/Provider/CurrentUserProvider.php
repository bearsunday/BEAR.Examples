<?php

declare(strict_types=1);

namespace BEAR\Kata\Provider;

use BEAR\Kata\Auth\AuthSessionInterface;
use BEAR\Kata\Auth\UserInterface;
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
