<?php

declare(strict_types=1);

namespace MyVendor\Cms\Command;

use Ray\MediaQuery\Annotation\DbQuery;

interface MediaCommandInterface
{
    #[DbQuery('create_media')]
    public function create(
        string $filename,
        string $mimeType,
        string $url,
        string|null $alt,
        int $width,
        int $height,
    ): void;

    #[DbQuery('delete_media')]
    public function delete(int $id): void;
}
