<?php

declare(strict_types=1);

namespace BEAR\Examples\Exception;

use RuntimeException;

final class InvalidAuthProviderException extends RuntimeException
{
    public function __construct(string $provider)
    {
        parent::__construct("Unsupported CMS_AUTH_PROVIDER '{$provider}'. Supported values: google, auth0.");
    }
}
