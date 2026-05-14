<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\MediaCommandInterface;
use MyVendor\Cms\Query\MediaQueryInterface;

use function assert;

#[Alps('Media')]
class Media extends ResourceObject
{
    public function __construct(
        private readonly MediaQueryInterface $media,
        private readonly MediaCommandInterface $mediaCmd,
    ) {
    }

    #[Alps('goMedia')]
    #[JsonSchema('media.json')]
    #[Cli(name: 'media-show', description: 'Show a media item by id', output: 'filename')]
    public function onGet(
        #[Option(shortName: 'i', description: 'Media id')]
        int $id,
    ): static {
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

    #[Alps('doCreateMedia')]
    #[JsonSchema(schema: 'write_response.json', params: 'media_create.json')]
    #[Cli(name: 'media-add', description: 'Register a new media item')]
    public function onPost(
        #[Option(shortName: 'f', description: 'File name')]
        string $filename,
        #[Option(shortName: 'm', description: 'MIME type')]
        string $mimeType,
        #[Option(shortName: 'u', description: 'Source URL')]
        string $url,
        #[Option(shortName: 'a', description: 'Alt text')]
        string|null $alt = null,
        #[Option(shortName: 'W', description: 'Width in pixels')]
        int $width = 0,
        #[Option(shortName: 'H', description: 'Height in pixels')]
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

    #[Alps('doDeleteMedia')]
    #[Cli(name: 'media-delete', description: 'Delete a media item')]
    public function onDelete(
        #[Option(shortName: 'i', description: 'Media id')]
        int $id,
    ): static {
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
