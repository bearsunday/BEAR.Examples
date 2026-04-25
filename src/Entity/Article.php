<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

final readonly class Article
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';

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

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function belongsToCategory(int $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }

    public function isWrittenBy(int $authorId): bool
    {
        return $this->authorId === $authorId;
    }
}
