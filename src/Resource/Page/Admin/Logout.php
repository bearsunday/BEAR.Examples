<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Auth\AuthSessionInterface;
use Ray\Csrf\Attribute\CsrfToken;
use Ray\Csrf\Attribute\SameOrigin;

/** @property array{message?: string} $body */
class Logout extends ResourceObject
{
    public function __construct(
        private readonly AuthSessionInterface $session,
    ) {
    }

    public function onGet(): static
    {
        $this->code = 405;
        $this->body = ['message' => 'Method not allowed'];

        return $this;
    }

    #[SameOrigin]
    #[CsrfToken]
    public function onPost(): static
    {
        $this->session->logout();
        $this->code = 303;
        $this->headers['Location'] = '/';
        $this->body = [];

        return $this;
    }
}
