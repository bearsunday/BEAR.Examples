<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

final readonly class Tag
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
    ) {
    }
}
