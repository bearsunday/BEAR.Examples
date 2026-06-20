<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\App;

use Koriym\FileUpload\ErrorFileUpload;
use Koriym\FileUpload\FileUpload;
use BEAR\Examples\AbstractAppTestCase;
use BEAR\Examples\Entity\Media;
use BEAR\Examples\Query\MediaCommandInterface;
use BEAR\Examples\Query\MediaQueryInterface;
use RuntimeException;

use function base64_decode;
use function file_put_contents;
use function glob;
use function is_file;
use function mkdir;
use function putenv;
use function rmdir;
use function sys_get_temp_dir;
use function tempnam;
use function uniqid;
use function unlink;

use const UPLOAD_ERR_NO_FILE;
use const UPLOAD_ERR_OK;

final class MediaUploadTest extends AbstractAppTestCase
{
    private string $uploadDir = '';

    /** @var list<string> */
    private array $fixtures = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploadDir = sys_get_temp_dir() . '/cms_upload_' . uniqid();
        mkdir($this->uploadDir);
        putenv('CMS_UPLOAD_DIR=' . $this->uploadDir);
        putenv('CMS_UPLOAD_BASE_URL=/test-uploads');
    }

    protected function tearDown(): void
    {
        putenv('CMS_UPLOAD_DIR');
        putenv('CMS_UPLOAD_BASE_URL');
        foreach ((array) glob($this->uploadDir . '/*') as $path) {
            if (! is_file((string) $path)) {
                continue;
            }

            unlink((string) $path);
        }

        foreach ($this->fixtures as $path) {
            if (! is_file($path)) {
                continue;
            }

            unlink($path);
        }

        if ($this->uploadDir === '') {
            return;
        }

        rmdir($this->uploadDir);
    }

    public function testUploadImageCreatesMedia(): void
    {
        $file = FileUpload::fromFile($this->pngFixture());
        $this->assertInstanceOf(FileUpload::class, $file);

        $ro = $this->resource->post('app://self/media-upload', [
            'file' => $file,
            'alt' => 'Uploaded through InputFile',
        ]);

        $this->assertSame(201, $ro->code);
        $this->assertIsInt($ro->body['id']);
        $this->assertStringStartsWith('/media?id=', $ro->headers['Location']);
        $this->assertStringStartsWith('/test-uploads/', $ro->body['url']);

        $files = (array) glob($this->uploadDir . '/*-upload.png');
        $this->assertCount(1, $files);
    }

    public function testUploadErrorReturnsBadRequest(): void
    {
        $ro = $this->resource->post('app://self/media-upload', [
            'file' => new ErrorFileUpload([
                'name' => '',
                'type' => '',
                'size' => 0,
                'tmp_name' => '',
                'error' => UPLOAD_ERR_NO_FILE,
            ]),
        ]);

        $this->assertSame(400, $ro->code);
        $this->assertSame('No file was uploaded', $ro->body['message']);
    }

    public function testDisallowedMimeTypeReturnsBadRequest(): void
    {
        $path = $this->tempFile('plain.txt', 'plain text');
        $file = FileUpload::fromFile($path);
        $this->assertInstanceOf(FileUpload::class, $file);

        $ro = $this->resource->post('app://self/media-upload', ['file' => $file]);

        $this->assertSame(400, $ro->code);
        $this->assertSame('Uploaded file MIME type is not allowed', $ro->body['message']);
    }

    public function testSvgUploadReturnsBadRequest(): void
    {
        $file = FileUpload::create([
            'name' => 'vector.svg',
            'type' => 'image/svg+xml',
            'size' => 64,
            'tmp_name' => '/tmp/does-not-need-to-exist',
            'error' => UPLOAD_ERR_OK,
        ]);
        $this->assertInstanceOf(FileUpload::class, $file);

        $ro = $this->resource->post('app://self/media-upload', ['file' => $file]);

        $this->assertSame(400, $ro->code);
        $this->assertSame('Uploaded file MIME type is not allowed', $ro->body['message']);
    }

    public function testMovedFileIsRemovedWhenMetadataRegistrationFails(): void
    {
        $file = FileUpload::fromFile($this->pngFixture());
        $this->assertInstanceOf(FileUpload::class, $file);

        $resource = new MediaUpload(
            new class implements MediaQueryInterface {
                public function item(int $id): Media|null
                {
                    return null;
                }

                public function byFilename(string $filename): Media|null
                {
                    return null;
                }
            },
            new class implements MediaCommandInterface {
                public function add(
                    string $filename,
                    string $mimeType,
                    string $url,
                    string|null $alt,
                    int $width,
                    int $height,
                ): void {
                    throw new RuntimeException('metadata write failed');
                }

                public function delete(int $id): void
                {
                }
            },
        );

        $ro = $resource->onPost($file);

        $this->assertSame(500, $ro->code);
        $this->assertSame('Uploaded media metadata could not be stored', $ro->body['message']);
        $this->assertSame([], (array) glob($this->uploadDir . '/*-upload.png'));
    }

    public function testOversizedFileReturnsBadRequestBeforeMove(): void
    {
        $file = FileUpload::create([
            'name' => 'large.png',
            'type' => 'image/png',
            'size' => 5_242_881,
            'tmp_name' => '/tmp/does-not-need-to-exist',
            'error' => UPLOAD_ERR_OK,
        ]);
        $this->assertInstanceOf(FileUpload::class, $file);

        $ro = $this->resource->post('app://self/media-upload', ['file' => $file]);

        $this->assertSame(400, $ro->code);
        $this->assertSame('Uploaded file exceeds 5242880 bytes', $ro->body['message']);
    }

    public function testEmptyFileReturnsBadRequest(): void
    {
        $file = FileUpload::create([
            'name' => 'empty.png',
            'type' => 'image/png',
            'size' => 0,
            'tmp_name' => '/tmp/does-not-need-to-exist',
            'error' => UPLOAD_ERR_OK,
        ]);
        $this->assertInstanceOf(FileUpload::class, $file);

        $ro = $this->resource->post('app://self/media-upload', ['file' => $file]);

        $this->assertSame(400, $ro->code);
        $this->assertSame('Uploaded file is empty', $ro->body['message']);
    }

    private function pngFixture(): string
    {
        return $this->tempFile(
            'upload.png',
            (string) base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAFgwJ/lx3N5wAAAABJRU5ErkJggg==',
                true,
            ),
        );
    }

    private function tempFile(string $filename, string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cms_upload_fixture_');
        $this->assertIsString($path);
        $namedPath = $path . '-' . $filename;
        file_put_contents($namedPath, $contents);
        unlink($path);
        $this->fixtures[] = $namedPath;

        return $namedPath;
    }
}
