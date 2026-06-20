<?php

declare(strict_types=1);

namespace BEAR\Examples\Integration;

use function uniqid;

final class AuthorMySQLTest extends AbstractMySQLTestCase
{
    public function testReadAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/author', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertNotEmpty($ro->body['name']);
        $this->assertStringContainsString('@', (string) $ro->body['email']);
    }

    public function testCreateThenUpdateAgainstRealDb(): void
    {
        $email = 'integration-' . uniqid() . '@example.com';
        $post = $this->resource->post('app://self/author', [
            'name' => 'Integration Author',
            'email' => $email,
            'bio' => 'Created by integration test.',
        ]);
        $this->assertSame(201, $post->code);
        $this->assertIsInt($post->body['id']);
        $id = $post->body['id'];

        $put = $this->resource->put('app://self/author', [
            'id' => $id,
            'name' => 'Renamed Author',
            'email' => $email,
            'bio' => 'Updated bio.',
        ]);
        $this->assertSame(200, $put->code);

        $get = $this->resource->get('app://self/author', ['id' => $id]);
        $this->assertSame('Renamed Author', $get->body['name']);
        $this->assertSame('Updated bio.', $get->body['bio']);
    }
}
