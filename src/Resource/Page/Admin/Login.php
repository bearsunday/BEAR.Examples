<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page\Admin;

use BEAR\Kata\Auth\AuthInterface;
use BEAR\Kata\Auth\AuthSessionInterface;
use BEAR\Resource\ResourceObject;

/** @property array{} $body */
class Login extends ResourceObject
{
    public function __construct(
        private readonly AuthInterface $auth,
        private readonly AuthSessionInterface $session,
    ) {
    }

    public function onGet(): static
    {
        $state = $this->session->issueState();
        $this->code = 302;
        $this->headers['Location'] = $this->auth->getAuthorizationUrl($state);
        $this->body = [];

        return $this;
    }
}
