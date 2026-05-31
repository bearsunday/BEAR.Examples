<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Auth\AdminGuard;
use MyVendor\Cms\Auth\AdminUserInterface;

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
