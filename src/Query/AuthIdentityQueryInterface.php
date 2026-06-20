<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use BEAR\Examples\Entity\AuthIdentity;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthIdentityQueryInterface
{
    #[DbQuery('auth_identity_by_provider_subject')]
    public function byProviderSubject(string $provider, string $subject): AuthIdentity|null;
}
