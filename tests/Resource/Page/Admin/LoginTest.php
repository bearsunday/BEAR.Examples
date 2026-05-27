<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;
use MyVendor\Cms\Auth\AuthenticatedUser;
use MyVendor\Cms\Auth\AuthInterface;
use MyVendor\Cms\Exception\OAuthConfigurationException;
use MyVendor\Cms\Exception\UnauthenticatedException;
use MyVendor\Cms\Fake\FakeAuthSession;

final class LoginTest extends AbstractPageTestCase
{
    public function testLoginRedirectsToOAuthProvider(): void
    {
        $ro = $this->resource->get('page://self/admin/login');

        $this->assertSame(302, $ro->code);
        $this->assertSame('https://example.test/fake-auth/authorize?state=fake-state', $ro->headers['Location']);
        $this->assertSame('', $ro->toString());
    }

    public function testCallbackCreatesAdminSession(): void
    {
        $this->resource->get('page://self/admin/login');
        $callback = $this->resource->get('page://self/admin/callback', [
            'code' => 'fake-code',
            'state' => 'fake-state',
        ]);

        $this->assertSame(303, $callback->code);
        $this->assertSame('/admin/index', $callback->headers['Location']);

        $admin = $this->resource->get('page://self/admin/index');
        $this->assertSame(200, $admin->code);
    }

    public function testLoginShowsLocalErrorWhenOAuthIsNotConfigured(): void
    {
        $ro = (new Login(
            new class implements AuthInterface {
                public function getAuthorizationUrl(string|null $state = null): string
                {
                    unset($state);

                    throw new OAuthConfigurationException();
                }

                public function authenticate(string $code, string $state): AuthenticatedUser
                {
                    unset($code, $state);

                    throw new OAuthConfigurationException();
                }
            },
            new FakeAuthSession(),
        ))->onGet();

        $this->assertSame(503, $ro->code);
        $this->assertSame('Google OAuth is not configured.', $ro->body['message']);
    }

    public function testCallbackRejectsInvalidState(): void
    {
        $callback = $this->resource->get('page://self/admin/callback', [
            'code' => 'fake-code',
            'state' => 'bad-state',
        ]);

        $this->assertSame(401, $callback->code);
        $this->assertSame('Authentication failed', $callback->body['message']);
    }

    public function testLogoutClearsAdminSession(): void
    {
        $this->resource->get('page://self/admin/login');
        $this->resource->get('page://self/admin/callback', [
            'code' => 'fake-code',
            'state' => 'fake-state',
        ]);

        $logout = $this->resource->post('page://self/admin/logout');

        $this->assertSame(303, $logout->code);
        $this->assertSame('/', $logout->headers['Location']);

        $this->expectException(UnauthenticatedException::class);
        $this->expectExceptionCode(401);
        $this->resource->get('page://self/admin/index');
    }

    public function testLogoutGetDoesNotClearAdminSession(): void
    {
        $this->resource->get('page://self/admin/login');
        $this->resource->get('page://self/admin/callback', [
            'code' => 'fake-code',
            'state' => 'fake-state',
        ]);

        $logout = $this->resource->get('page://self/admin/logout');

        $this->assertSame(405, $logout->code);
        $this->assertSame('Method not allowed', $logout->body['message']);

        $admin = $this->resource->get('page://self/admin/index');
        $this->assertSame(200, $admin->code);
    }
}
