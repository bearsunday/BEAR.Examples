<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page\Admin;

use BEAR\Kata\Auth\AdminGuard;
use BEAR\Kata\Auth\AdminUserInterface;
use BEAR\Resource\ResourceObject;

/** @property array{admin: AdminUserInterface} $body */
class Index extends ResourceObject
{
    public function __construct(
        private readonly AdminGuard $admin,
    ) {
    }

    public function onGet(): static
    {
        $this->body = ['admin' => $this->admin->user()];

        return $this;
    }
}
