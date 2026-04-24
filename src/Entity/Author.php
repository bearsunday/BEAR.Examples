<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

final readonly class Author
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $bio,
    ) {
    }
}
