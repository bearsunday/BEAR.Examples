<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use MyVendor\Cms\AbstractAppTestCase;

final class AuthorTest extends AbstractAppTestCase
{
    public function testGet(): void
    {
        $ro = $this->resource->get('app://self/author', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertNotEmpty($ro->body['name']);
    }

    public function testCreateAndUpdate(): void
    {
        $email = 'test-' . uniqid() . '@example.com';
        $post = $this->resource->post('app://self/author', [
            'name' => 'Test Author',
            'email' => $email,
            'bio' => 'Hello.',
        ]);
        $this->assertSame(201, $post->code);

        $id = $post->body['id'];
        $put = $this->resource->put('app://self/author', [
            'id' => $id,
            'name' => 'Renamed',
            'email' => $email,
            'bio' => 'Updated bio',
        ]);
        $this->assertSame(200, $put->code);

        $get = $this->resource->get('app://self/author', ['id' => $id]);
        $this->assertSame('Renamed', $get->body['name']);
        $this->assertSame('Updated bio', $get->body['bio']);
    }
}
