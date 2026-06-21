<?php

declare(strict_types=1);

namespace BEAR\Kata\Query;

use BEAR\Kata\Entity\AuthIdentity;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthIdentityQueryInterface
{
    #[DbQuery('auth_identity_by_provider_subject')]
    public function byProviderSubject(string $provider, string $subject): AuthIdentity|null;
}
