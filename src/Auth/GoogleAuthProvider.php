<?php

declare(strict_types=1);

namespace BEAR\Kata\Auth;

use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\GoogleUser;
use BEAR\Kata\Exception\UnexpectedAuthProviderResponseException;

/**
 * Real Google OAuth provider.
 *
 * Requires GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET, GOOGLE_REDIRECT_URI in
 * the environment (.env). Not exercised by the test suite — see
 * FakeAuthProvider for the test path.
 */
final class GoogleAuthProvider implements AuthInterface
{
    public function __construct(
        private readonly Google $provider,
    ) {
    }

    public function getAuthorizationUrl(string|null $state = null): string
    {
        $options = [
            'scope' => ['openid', 'email', 'profile'],
        ];
        if ($state !== null) {
            $options['state'] = $state;
        }

        return $this->provider->getAuthorizationUrl($options);
    }

    public function authenticate(string $code, string $state): AuthenticatedUser
    {
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $user = $this->provider->getResourceOwner($token);
        if (! $user instanceof GoogleUser) {
            throw new UnexpectedAuthProviderResponseException('Unexpected resource owner type from Google.');
        }

        return new AuthenticatedUser(
            id: (string) $user->getId(),
            email: (string) $user->getEmail(),
            name: (string) $user->getName(),
            provider: 'google',
            subject: (string) $user->getId(),
        );
    }
}
