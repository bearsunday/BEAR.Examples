<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Examples\Auth\AuthInterface;
use BEAR\Examples\Auth\AuthorIdentityResolver;
use BEAR\Examples\Auth\AuthSessionInterface;
use Throwable;

/** @property array{message: string}|array{} $body */
class Callback extends ResourceObject
{
    public function __construct(
        private readonly AuthInterface $auth,
        private readonly AuthSessionInterface $session,
        private readonly AuthorIdentityResolver $identity,
    ) {
    }

    public function onGet(string $code = '', string $state = ''): static
    {
        if ($code === '' || ! $this->session->consumeState($state)) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'Authentication failed'];

            return $this;
        }

        try {
            $user = $this->auth->authenticate($code, $state);
        } catch (Throwable) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'Authentication failed'];

            return $this;
        }

        $authorId = $this->identity->resolveAuthorId($user);
        if ($authorId === null) {
            $this->code = Code::FORBIDDEN;
            $this->body = ['message' => 'Forbidden'];

            return $this;
        }

        $this->session->login($user, $authorId);
        $this->code = 303;
        $this->headers['Location'] = '/admin/index';
        $this->body = [];

        return $this;
    }
}
