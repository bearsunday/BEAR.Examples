<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\AuthIdentity;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthIdentityQueryInterface
{
    #[DbQuery('auth_identity_by_provider_subject')]
    public function byProviderSubject(string $provider, string $subject): AuthIdentity|null;
}
