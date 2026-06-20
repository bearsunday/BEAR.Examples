<?php

declare(strict_types=1);

namespace BEAR\Examples\Smoke;

use BEAR\Examples\Auth\GoogleAuthProvider;
use BEAR\Examples\Provider\GoogleProvider;
use PHPUnit\Framework\TestCase;

use function getenv;
use function is_string;
use function parse_str;
use function parse_url;

use const PHP_URL_QUERY;

final class GoogleAuthProviderSmokeTest extends TestCase
{
    public function testGoogleAuthorizationUrlCanBeGeneratedFromEnvironment(): void
    {
        $clientId = self::requiredEnv('GOOGLE_CLIENT_ID');
        self::requiredEnv('GOOGLE_CLIENT_SECRET');
        $redirectUri = self::requiredEnv('GOOGLE_REDIRECT_URI');

        $auth = new GoogleAuthProvider((new GoogleProvider())->get());
        $url = $auth->getAuthorizationUrl('smoke-state');

        $queryString = parse_url($url, PHP_URL_QUERY);
        $this->assertIsString($queryString);
        parse_str($queryString, $query);

        $this->assertSame($clientId, $query['client_id'] ?? null);
        $this->assertSame($redirectUri, $query['redirect_uri'] ?? null);
        $this->assertSame('smoke-state', $query['state'] ?? null);
        $this->assertStringContainsString('openid', (string) ($query['scope'] ?? ''));
        $this->assertStringContainsString('email', (string) ($query['scope'] ?? ''));
        $this->assertStringContainsString('profile', (string) ($query['scope'] ?? ''));
    }

    private static function requiredEnv(string $name): string
    {
        $value = getenv($name);
        if (! is_string($value) || $value === '') {
            self::markTestSkipped($name . ' is not configured.');
        }

        return $value;
    }
}
