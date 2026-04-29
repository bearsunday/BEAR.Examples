<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\MediaCommandInterface;
use MyVendor\Cms\Query\MediaQueryInterface;

use function assert;

class Media extends ResourceObject
{
    public function __construct(
        private readonly MediaQueryInterface $media,
        private readonly MediaCommandInterface $mediaCmd,
    ) {
    }

    #[JsonSchema('media.json')]
    public function onGet(int $id): static
    {
        $media = $this->media->item($id);
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

    #[JsonSchema(schema: 'write_response.json', params: 'media_create.json')]
    public function onPost(
        string $filename,
        string $mimeType,
        string $url,
        string|null $alt = null,
        int $width = 0,
        int $height = 0,
    ): static {
        $this->mediaCmd->add(
            $filename,
            $mimeType,
            $url,
            $alt,
            $width,
            $height,
        );
        // byFilename after add is invariant per docs/conventions.md §4.
        $created = $this->media->byFilename($filename);
        assert($created !== null);
        $this->code = Code::CREATED;
        $this->headers['Location'] = '/media?id=' . $created->id;
        $this->body = ['id' => $created->id, 'filename' => $filename];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->media->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Media not found', 'id' => $id];

            return $this;
        }

        $this->mediaCmd->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
