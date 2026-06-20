<?php

declare(strict_types=1);

namespace BEAR\Examples\Entity;

final readonly class Media
{
    public function __construct(
        public int $id,
        public string $filename,
        public string $mimeType,
        public string $url,
        public string|null $alt,
        public int $width,
        public int $height,
    ) {
    }
}
