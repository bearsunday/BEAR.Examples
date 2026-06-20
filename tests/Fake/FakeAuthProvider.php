<?php

declare(strict_types=1);

namespace BEAR\Examples\Fake;

use BEAR\Examples\Auth\AuthenticatedUser;
use BEAR\Examples\Auth\AuthInterface;

/**
 * In-memory replacement for GoogleAuthProvider used by tests / fake-hal-api-app.
 *
 * authenticate() ignores the code/state and returns a deterministic user so
 * Resources gated by AuthInterface can be exercised without a real OAuth round-trip.
 */
final class FakeAuthProvider implements AuthInterface
{
    public function __construct(
        private readonly string $userId = 'fake-user-1',
        private readonly string $email = 'evelyn.moore1@example.com',
        private readonly string $name = 'Evelyn Moore',
        private readonly string $provider = 'google',
    ) {
    }

    public function getAuthorizationUrl(string|null $state = null): string
    {
        return 'https://example.test/fake-auth/authorize?state=' . (string) $state;
    }

    public function authenticate(string $code, string $state): AuthenticatedUser
    {
        return new AuthenticatedUser(
            id: $this->userId,
            email: $this->email,
            name: $this->name,
            provider: $this->provider,
            subject: $this->userId,
        );
    }
}
