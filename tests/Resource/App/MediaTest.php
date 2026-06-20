<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\App;

use BEAR\Examples\AbstractAppTestCase;

use function uniqid;

final class MediaTest extends AbstractAppTestCase
{
    public function testGet(): void
    {
        $ro = $this->resource->get('app://self/media', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertNotEmpty($ro->body['filename']);
    }

    public function testCreateAndDelete(): void
    {
        $filename = 'upload-' . uniqid() . '.png';
        $post = $this->resource->post('app://self/media', [
            'filename' => $filename,
            'mimeType' => 'image/png',
            'url' => '/media/' . $filename,
            'alt' => 'test',
            'width' => 100,
            'height' => 100,
        ]);
        $this->assertSame(201, $post->code);

        $id = $post->body['id'];
        $del = $this->resource->delete('app://self/media', ['id' => $id]);
        $this->assertSame(204, $del->code);
    }
}
