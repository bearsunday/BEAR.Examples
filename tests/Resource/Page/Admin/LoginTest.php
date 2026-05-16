<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use MyVendor\Cms\AbstractPageTestCase;

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

        $logout = $this->resource->get('page://self/admin/logout');

        $this->assertSame(303, $logout->code);
        $this->assertSame('/', $logout->headers['Location']);
    }
}
