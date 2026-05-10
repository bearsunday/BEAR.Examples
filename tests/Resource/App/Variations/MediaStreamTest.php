<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\TransferInterface;
use MyVendor\Cms\AbstractAppTestCase;

use function file_get_contents;
use function is_resource;
use function ob_get_clean;
use function ob_start;
use function strlen;

final class MediaStreamTest extends AbstractAppTestCase
{
    public function testOnGetReturnsMediaStream(): void
    {
        $expected = file_get_contents(__DIR__ . '/../../../../var/media/media-005.svg');
        $this->assertNotFalse($expected);
        $expectedBytes = (string) $expected;
        $ro = $this->resource->get('app://self/variations/mediastream', ['id' => 5]);

        $this->assertSame(200, $ro->code);
        $this->assertSame('image/svg+xml', $ro->headers['Content-Type']);
        $this->assertSame((string) strlen($expectedBytes), $ro->headers['Content-Length']);
        $this->assertSame(
            'attachment; filename="media-005.svg"',
            $ro->headers['Content-Disposition'],
        );
        $this->assertTrue(is_resource($ro->body));

        ob_start();
        $ro->transfer(new class implements TransferInterface {
            /** @param array<string, mixed> $server */
            public function __invoke(ResourceObject $ro, array $server): void
            {
                unset($ro, $server);
            }
        }, []);
        $streamed = ob_get_clean();

        $this->assertSame(
            $expectedBytes,
            $streamed,
        );
    }

    public function testOnGetReturnsNotFoundForUnknownMedia(): void
    {
        $ro = $this->resource->get('app://self/variations/mediastream', ['id' => 9999]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('application/json', $ro->headers['Content-Type']);
        $this->assertArrayNotHasKey('Content-Length', $ro->headers);
        $this->assertArrayNotHasKey('Content-Disposition', $ro->headers);
        $this->assertSame('Media not found', $ro->body['message']);
        $this->assertSame(9999, $ro->body['id']);
    }

    public function testOnGetReturnsNotFoundWhenMediaFileIsMissing(): void
    {
        $ro = $this->resource->get('app://self/variations/mediastream', ['id' => 1]);

        $this->assertSame(404, $ro->code);
        $this->assertSame('application/json', $ro->headers['Content-Type']);
        $this->assertArrayNotHasKey('Content-Length', $ro->headers);
        $this->assertArrayNotHasKey('Content-Disposition', $ro->headers);
        $this->assertSame('Media file not found', $ro->body['message']);
        $this->assertSame(1, $ro->body['id']);
        $this->assertSame('media-001.jpg', $ro->body['filename']);
    }
}
