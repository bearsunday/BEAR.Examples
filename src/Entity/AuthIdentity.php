<?php

declare(strict_types=1);

namespace BEAR\Examples\Entity;

final readonly class AuthIdentity
{
    public function __construct(
        public int $id,
        public string $provider,
        public string $subject,
        public int $authorId,
        public string $email,
        public string $name,
    ) {
    }
}
