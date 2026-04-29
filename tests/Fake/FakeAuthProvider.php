<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Auth\AuthenticatedUser;
use MyVendor\Cms\Auth\AuthInterface;

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
        private readonly string $email = 'demo@example.com',
        private readonly string $name = 'Demo User',
    ) {
    }

    public function getAuthorizationUrl(): string
    {
        return 'https://example.test/fake-auth/authorize';
    }

    public function authenticate(string $code, string $state): AuthenticatedUser
    {
        return new AuthenticatedUser(id: $this->userId, email: $this->email, name: $this->name);
    }
}
