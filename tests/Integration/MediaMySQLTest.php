<?php

declare(strict_types=1);

namespace BEAR\Examples\Integration;

use function uniqid;

final class MediaMySQLTest extends AbstractMySQLTestCase
{
    public function testReadAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/media', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertSame('media-001.jpg', $ro->body['filename']);
        $this->assertSame('image/jpeg', $ro->body['mimeType']);
        $this->assertSame('/media/media-001.jpg', $ro->body['url']);
    }

    public function testCreateThenDeleteAgainstRealDb(): void
    {
        $filename = 'integration-media-' . uniqid() . '.png';
        $post = $this->resource->post('app://self/media', [
            'filename' => $filename,
            'mimeType' => 'image/png',
            'url' => '/media/' . $filename,
            'alt' => 'Integration test image',
            'width' => 640,
            'height' => 480,
        ]);
        $this->assertSame(201, $post->code);
        $newId = $post->body['id'];

        $get = $this->resource->get('app://self/media', ['id' => $newId]);
        $this->assertSame(200, $get->code);
        $this->assertSame($filename, $get->body['filename']);
        $this->assertSame('Integration test image', $get->body['alt']);
        $this->assertSame(640, $get->body['width']);
        $this->assertSame(480, $get->body['height']);

        $del = $this->resource->delete('app://self/media', ['id' => $newId]);
        $this->assertSame(204, $del->code);

        $missing = $this->resource->get('app://self/media', ['id' => $newId]);
        $this->assertSame(404, $missing->code);
    }
}
