<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Auth\AuthSessionInterface;

/** @property array{} $body */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly AuthSessionInterface $session,
    ) {
    }

    public function onGet(): static
    {
        $this->session->logout();
        $this->code = 303;
        $this->headers['Location'] = '/';
        $this->body = [];

        return $this;
    }
}
