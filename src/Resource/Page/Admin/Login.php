<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Auth\AuthInterface;
use MyVendor\Cms\Auth\AuthSessionInterface;
use MyVendor\Cms\Exception\OAuthConfigurationException;

/** @property array{message?: string} $body */
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
        try {
            $authorizationUrl = $this->auth->getAuthorizationUrl($state);
        } catch (OAuthConfigurationException $e) {
            $this->code = $e->getCode();
            $this->body = ['message' => $e->getMessage()];

            return $this;
        }

        $this->code = 302;
        $this->headers['Location'] = $authorizationUrl;
        $this->body = [];

        return $this;
    }
}
