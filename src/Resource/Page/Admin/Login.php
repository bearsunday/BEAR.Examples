<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Auth\AuthInterface;
use BEAR\Examples\Auth\AuthSessionInterface;

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
