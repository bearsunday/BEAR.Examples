<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\MediaCommandInterface;
use MyVendor\Cms\Query\MediaQueryInterface;

class Media extends ResourceObject
{
    public function __construct(
        private readonly MediaQueryInterface $mediaQuery,
        private readonly MediaCommandInterface $mediaCommand,
    ) {
    }

    #[JsonSchema('media.json')]
    public function onGet(int $id): static
    {
        $media = $this->mediaQuery->getById($id);
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

    public function onPost(
        string $filename,
        string $mimeType,
        string $url,
        string|null $alt = null,
        int $width = 0,
        int $height = 0,
    ): static {
        $this->mediaCommand->add(
            filename: $filename,
            mimeType: $mimeType,
            url: $url,
            alt: $alt,
            width: $width,
            height: $height,
        );
        $created = $this->mediaQuery->getByFilename($filename);
        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/media?id=' . $created->id : '/media';
        $this->body = ['id' => $created?->id, 'filename' => $filename];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->mediaQuery->getById($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Media not found', 'id' => $id];

            return $this;
        }

        $this->mediaCommand->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
