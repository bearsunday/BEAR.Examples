<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use MyVendor\Cms\Query\MediaCommandInterface;
use MyVendor\Cms\Query\MediaQueryInterface;
use Ray\InputQuery\Attribute\InputFile;

use function basename;
use function bin2hex;
use function dirname;
use function getenv;
use function in_array;
use function is_dir;
use function mkdir;
use function preg_replace;
use function random_bytes;
use function rtrim;

#[Alps('MediaUpload')]
class MediaUpload extends ResourceObject
{
    private const int MAX_UPLOAD_BYTES = 5_242_880;
    private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
    private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

    public function __construct(
        private readonly MediaQueryInterface $media,
        private readonly MediaCommandInterface $mediaCmd,
    ) {
    }

    #[Alps('doUploadMediaFile')]
    #[JsonSchema(schema: 'write_response.json', params: 'media_upload.json')]
    public function onPost(
        #[InputFile(
            maxSize: self::MAX_UPLOAD_BYTES,
            allowedTypes: self::ALLOWED_MIME_TYPES,
            allowedExtensions: self::ALLOWED_EXTENSIONS,
        )]
        FileUpload|ErrorFileUpload $file,
        string|null $alt = null,
    ): static {
        if ($file instanceof ErrorFileUpload) {
            return $this->badRequest($file->message ?? 'File upload failed');
        }

        $invalidMessage = $this->invalidFileMessage($file);
        if ($invalidMessage !== null) {
            return $this->badRequest($invalidMessage);
        }

        $uploadDir = $this->uploadDir();
        if (! $this->ensureUploadDir($uploadDir)) {
            return $this->serverError('Upload directory is not writable');
        }

        $filename = $this->storedFilename($file);
        if (! $file->move($uploadDir . '/' . $filename)) {
            return $this->serverError('Uploaded file could not be stored');
        }

        return $this->registerMedia($file, $filename, $alt);
    }

    private function invalidFileMessage(FileUpload $file): string|null
    {
        if ($file->size <= 0) {
            return 'Uploaded file is empty';
        }

        if ($file->size > self::MAX_UPLOAD_BYTES) {
            return 'Uploaded file exceeds 5242880 bytes';
        }

        if (! in_array($file->type, self::ALLOWED_MIME_TYPES, true)) {
            return 'Uploaded file MIME type is not allowed';
        }

        if (! in_array((string) $file->extension, self::ALLOWED_EXTENSIONS, true)) {
            return 'Uploaded file extension is not allowed';
        }

        return null;
    }

    private function ensureUploadDir(string $uploadDir): bool
    {
        return is_dir($uploadDir) || mkdir($uploadDir, 0775, true);
    }

    private function registerMedia(FileUpload $file, string $filename, string|null $alt): static
    {
        $this->mediaCmd->add(
            $filename,
            $file->type,
            $this->baseUrl() . '/' . $filename,
            $alt,
            0,
            0,
        );
        $created = $this->media->byFilename($filename);
        if ($created === null) {
            return $this->serverError('Uploaded media metadata was not found');
        }

        $this->code = Code::CREATED;
        $this->headers['Location'] = '/media?id=' . $created->id;
        $this->body = [
            'id' => $created->id,
            'filename' => $filename,
            'url' => $created->url,
        ];

        return $this;
    }

    private function serverError(string $message): static
    {
        $this->code = Code::ERROR;
        $this->body = ['message' => $message];

        return $this;
    }

    private function badRequest(string $message): static
    {
        $this->code = Code::BAD_REQUEST;
        $this->body = ['message' => $message];

        return $this;
    }

    private function uploadDir(): string
    {
        $dir = getenv('CMS_UPLOAD_DIR');

        return $dir === false || $dir === '' ? dirname(__DIR__, 3) . '/var/tmp/uploads' : (string) $dir;
    }

    private function baseUrl(): string
    {
        $baseUrl = getenv('CMS_UPLOAD_BASE_URL');

        return rtrim($baseUrl === false || $baseUrl === '' ? '/uploads' : (string) $baseUrl, '/');
    }

    private function storedFilename(FileUpload $file): string
    {
        $basename = basename($file->name);
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename);
        $safe = $safe === null || $safe === '' ? 'upload.' . (string) $file->extension : $safe;

        return bin2hex(random_bytes(8)) . '-' . $safe;
    }
}
