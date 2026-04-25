<?php

declare(strict_types=1);

namespace MyVendor\Cms\Provider;

use League\OAuth2\Client\Provider\Google;
use Ray\Di\ProviderInterface;

use function getenv;

/** @implements ProviderInterface<Google> */
final class GoogleProvider implements ProviderInterface
{
    public function get(): Google
    {
        return new Google([
            'clientId' => (string) getenv('GOOGLE_CLIENT_ID'),
            'clientSecret' => (string) getenv('GOOGLE_CLIENT_SECRET'),
            'redirectUri' => (string) getenv('GOOGLE_REDIRECT_URI'),
        ]);
    }
}
