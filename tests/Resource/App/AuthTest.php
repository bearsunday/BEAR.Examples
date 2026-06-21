<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\Kata\AbstractAppTestCase;

final class AuthTest extends AbstractAppTestCase
{
    public function testGetReturnsAuthorizationUrl(): void
    {
        $ro = $this->resource->get('app://self/auth', []);
        $this->assertSame(200, $ro->code);
        $this->assertStringContainsString('://', (string) $ro->body['authorizationUrl']);
    }

    public function testPostExchangesCodeForUser(): void
    {
        $ro = $this->resource->post('app://self/auth', ['code' => 'fake-code', 'state' => 'fake-state']);
        $this->assertSame(200, $ro->code);
        $this->assertSame('fake-user-1', $ro->body['id']);
        $this->assertSame('google', $ro->body['provider']);
        $this->assertSame('fake-user-1', $ro->body['subject']);
        $this->assertSame('evelyn.moore1@example.com', $ro->body['email']);
        $this->assertSame('Evelyn Moore', $ro->body['name']);
    }
}
