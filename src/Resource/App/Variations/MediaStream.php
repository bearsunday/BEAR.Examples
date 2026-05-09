<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Streamer\StreamTransferInject;
use MyVendor\Cms\Query\MediaQueryInterface;

use function basename;
use function fclose;
use function fopen;
use function fstat;
use function is_int;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function strtr;

/**
 * Comparison-only stream transfer.
 *
 * Canonical equivalent: App\Media::onGet(), which keeps the response JSON-shaped.
 */
class MediaStream extends ResourceObject
{
    use StreamTransferInject;

    public function __construct(
        private readonly MediaQueryInterface $media,
        private readonly AbstractAppMeta $appMeta,
    ) {
    }

    public function onGet(int $id): static
    {
        $media = $this->media->item($id);
        if ($media === null) {
            return $this->notFound($id, 'Media not found');
        }

        $filename = basename($media->filename);
        $path = $this->appMeta->appDir . '/var/media/' . $filename;
        $stream = $this->openReadStream($path);
        if ($stream === null) {
            return $this->notFound($id, 'Media file not found', $filename);
        }

        $size = $this->streamSize($stream);
        if ($size === null) {
            fclose($stream);

            return $this->notFound($id, 'Media file not found', $filename);
        }

        $this->headers['Content-Type'] = $media->mimeType;
        $this->headers['Content-Length'] = (string) $size;
        $this->headers['Content-Disposition'] = sprintf(
            'attachment; filename="%s"',
            strtr($filename, ['\\' => '\\\\', '"' => '\\"']),
        );
        $this->body = $stream;

        return $this;
    }

    /** @return resource|null */
    private function openReadStream(string $path)
    {
        set_error_handler(static function (): bool {
            return true;
        });

        try {
            $stream = fopen($path, 'rb');
        } finally {
            restore_error_handler();
        }

        return $stream === false ? null : $stream;
    }

    /** @param resource $stream */
    private function streamSize($stream): int|null
    {
        $stat = fstat($stream);

        return is_int($stat['size'] ?? null) ? $stat['size'] : null;
    }

    private function notFound(int $id, string $message, string|null $filename = null): static
    {
        $this->code = Code::NOT_FOUND;
        $this->headers['Content-Type'] = 'application/json';
        $this->body = ['message' => $message, 'id' => $id];
        if ($filename !== null) {
            $this->body['filename'] = $filename;
        }

        return $this;
    }
}
