<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $body,
        public string|null $excerpt,
        public string $status,
        public string|null $publishedAt,
        public int $authorId,
        public int $categoryId,
    ) {
    }
}
