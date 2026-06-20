<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface AuthIdentityCommandInterface
{
    #[DbQuery('auth_identity_add')]
    public function add(
        string $provider,
        string $subject,
        int $authorId,
        string $email,
        string $name,
    ): void;
}
