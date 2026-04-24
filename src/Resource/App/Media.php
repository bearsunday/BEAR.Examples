<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\MediaQueryInterface;

class Media extends ResourceObject
{
    public function __construct(
        private readonly MediaQueryInterface $mediaQuery,
    ) {
    }

    public function onGet(int $id): static
    {
        $media = $this->mediaQuery->get($id);
        if ($media === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Media not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $media->id,
            'filename' => $media->filename,
            'mimeType' => $media->mimeType,
            'url' => $media->url,
            'alt' => $media->alt,
            'width' => $media->width,
            'height' => $media->height,
        ];

        return $this;
    }
}
