<?php

declare(strict_types=1);

namespace BEAR\Examples\Auth;

interface AuthInterface
{
    /** Build the OAuth provider's authorisation URL (where to redirect the user). */
    public function getAuthorizationUrl(string|null $state = null): string;

    /** Exchange the authorisation code for an authenticated user. */
    public function authenticate(string $code, string $state): AuthenticatedUser;
}
