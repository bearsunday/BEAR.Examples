<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string|null $description,
        public int|null $parentId,
    ) {
    }
}
