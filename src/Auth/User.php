<?php

declare(strict_types=1);

namespace BEAR\Examples\Auth;

final readonly class User implements UserInterface
{
    public function __construct(
        public string $id,
        public string $email,
        public string $name,
    ) {
    }
}
