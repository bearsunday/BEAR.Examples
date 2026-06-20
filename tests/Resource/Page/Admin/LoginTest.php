<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page\Admin;

use BEAR\Resource\ResourceInterface;
use BEAR\Examples\AbstractPageTestCase;
use BEAR\Examples\Exception\UnauthenticatedException;
use BEAR\Examples\Fake\FakeAuth0Module;
use BEAR\Examples\Fake\FakeSqlQuery;
use BEAR\Examples\Injector;
use Ray\MediaQuery\SqlQueryInterface;

use function array_filter;
use function array_values;

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

    public function testCallbackCreatesAuth0IdentityMappingOnEmailFallback(): void
    {
        $injector = Injector::getOverrideInstance('html-test-hal-api-app', new FakeAuth0Module());
        $resource = $injector->getInstance(ResourceInterface::class);
        $sql = $injector->getInstance(SqlQueryInterface::class);
        $this->assertInstanceOf(FakeSqlQuery::class, $sql);

        $resource->get('page://self/admin/login');
        $callback = $resource->get('page://self/admin/callback', [
            'code' => 'fake-code',
            'state' => 'fake-state',
        ]);

        $this->assertSame(303, $callback->code);
        $writes = array_values(array_filter(
            $sql->execLog,
            static fn (array $row): bool => $row['sqlId'] === 'auth_identity_add',
        ));
        $this->assertCount(1, $writes);
        $this->assertSame([
            'provider' => 'auth0',
            'subject' => 'auth0|editor-1',
            'authorId' => 1,
            'email' => 'evelyn.moore1@example.com',
            'name' => 'Evelyn Moore',
        ], $writes[0]['values']);

        $sql->resetExecLog();
        $resource->get('page://self/admin/login');
        $second = $resource->get('page://self/admin/callback', [
            'code' => 'fake-code',
            'state' => 'fake-state',
        ]);

        $this->assertSame(303, $second->code);
        $this->assertSame([], array_values(array_filter(
            $sql->execLog,
            static fn (array $row): bool => $row['sqlId'] === 'auth_identity_add',
        )));
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
