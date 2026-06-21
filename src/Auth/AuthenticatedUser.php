<?php

declare(strict_types=1);

namespace BEAR\Kata\Auth;

final readonly class AuthenticatedUser
{
    public string $subject;

    public function __construct(
        public string $id,
        public string $email,
        public string $name,
        public string $provider = 'google',
        string|null $subject = null,
    ) {
        $this->subject = $subject ?? $id;
    }
}
