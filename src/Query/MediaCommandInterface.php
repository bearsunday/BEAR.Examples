<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface MediaCommandInterface
{
    #[DbQuery('media_add')]
    public function add(
        string $filename,
        string $mimeType,
        string $url,
        string|null $alt,
        int $width,
        int $height,
    ): void;

    #[DbQuery('media_delete')]
    public function delete(int $id): void;
}
