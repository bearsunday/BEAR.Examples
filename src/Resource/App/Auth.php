<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Auth\AuthInterface;
use Throwable;

/**
 * OAuth login resource.
 *
 * GET  app://self/auth        → returns the authorization URL the client should redirect to
 * POST app://self/auth        → exchanges {code, state} for an AuthenticatedUser
 *
 * The bound AuthInterface decides the backend (Google in production,
 * FakeAuthProvider in test / fake-hal-api-app).
 */
class Auth extends ResourceObject
{
    public function __construct(
        private readonly AuthInterface $auth,
    ) {
    }

    public function onGet(): static
    {
        $this->body = [
            'authorizationUrl' => $this->auth->getAuthorizationUrl(),
        ];

        return $this;
    }

    public function onPost(string $code, string $state): static
    {
        try {
            $user = $this->auth->authenticate($code, $state);
        } catch (Throwable $e) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'Authentication failed', 'reason' => $e->getMessage()];

            return $this;
        }

        $this->body = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];

        return $this;
    }
}
