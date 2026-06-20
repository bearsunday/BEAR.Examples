<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Examples\Auth\AuthInterface;
use BEAR\Examples\Input\AuthExchangeInput;
use Ray\InputQuery\Attribute\Input;
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

    #[JsonSchema('auth_authorization.json')]
    public function onGet(): static
    {
        $this->body = [
            'authorizationUrl' => $this->auth->getAuthorizationUrl(),
        ];

        return $this;
    }

    #[JsonSchema(schema: 'auth_response.json', params: 'auth_exchange.json')]
    public function onPost(#[Input] AuthExchangeInput $input): static
    {
        try {
            $user = $this->auth->authenticate($input->code, $input->state);
        } catch (Throwable) {
            // The OAuth-provider message can leak provider-internal detail
            // (token introspection results, server-side error strings). A
            // production CMS would log the exception via a logger binding;
            // the public response intentionally exposes no reason field.
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'Authentication failed'];

            return $this;
        }

        $this->body = [
            'id' => $user->id,
            'provider' => $user->provider,
            'subject' => $user->subject,
            'email' => $user->email,
            'name' => $user->name,
        ];

        return $this;
    }
}
