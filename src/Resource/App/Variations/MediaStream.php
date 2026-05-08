<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use BEAR\AppMeta\AbstractAppMeta;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Streamer\StreamTransferInject;
use MyVendor\Cms\Query\MediaQueryInterface;

use function assert;
use function basename;
use function filesize;
use function fopen;
use function is_file;
use function sprintf;
use function strtr;

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
            $this->code = Code::NOT_FOUND;
            $this->headers['Content-Type'] = 'application/json';
            $this->body = ['message' => 'Media not found', 'id' => $id];

            return $this;
        }

        $filename = basename($media->filename);
        $path = $this->appMeta->appDir . '/var/media/' . $filename;
        if (! is_file($path)) {
            $this->code = Code::NOT_FOUND;
            $this->headers['Content-Type'] = 'application/json';
            $this->body = ['message' => 'Media file not found', 'filename' => $filename];

            return $this;
        }

        $size = filesize($path);
        assert($size !== false);
        $stream = fopen($path, 'rb');
        assert($stream !== false);

        $this->headers['Content-Type'] = $media->mimeType;
        $this->headers['Content-Length'] = (string) $size;
        $this->headers['Content-Disposition'] = sprintf(
            'attachment; filename="%s"',
            strtr($filename, ['\\' => '\\\\', '"' => '\\"']),
        );
        $this->body = $stream;

        return $this;
    }
}
