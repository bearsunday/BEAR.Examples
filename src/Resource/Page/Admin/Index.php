<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Auth\AdminGuard;
use BEAR\Examples\Auth\AdminUserInterface;

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
