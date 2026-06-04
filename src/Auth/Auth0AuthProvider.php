<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

use Auth0\SDK\Contract\Auth0Interface;
use MyVendor\Cms\Exception\UnexpectedAuthProviderResponseException;

use function is_array;
use function is_string;
use function trim;

final class Auth0AuthProvider implements AuthInterface
{
    public function __construct(
        private readonly Auth0Interface $auth0,
    ) {
    }

    public function getAuthorizationUrl(string|null $state = null): string
    {
        $params = $state === null ? null : ['state' => $state];

        return $this->auth0->login(null, $params);
    }

    public function authenticate(string $code, string $state): AuthenticatedUser
    {
        if (! $this->auth0->exchange(null, $code, $state)) {
            throw new UnexpectedAuthProviderResponseException('Auth0 code exchange failed.');
        }

        $user = $this->auth0->getUser();
        if (! is_array($user)) {
            throw new UnexpectedAuthProviderResponseException('Auth0 did not return a user profile.');
        }

        return $this->authenticatedUser($user);
    }

    /** @param array<string, mixed> $user */
    private function authenticatedUser(array $user): AuthenticatedUser
    {
        $subject = $this->nonEmptyString($user['sub'] ?? null);
        $email = $this->nonEmptyString($user['email'] ?? null);
        if ($subject === null || $email === null) {
            throw new UnexpectedAuthProviderResponseException('Auth0 user profile is missing sub, email, or name.');
        }

        $name = $this->nonEmptyString($user['name'] ?? null) ?? $email;

        return new AuthenticatedUser(
            id: $subject,
            email: $email,
            name: $name,
            provider: 'auth0',
            subject: $subject,
        );
    }

    private function nonEmptyString(mixed $value): string|null
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
