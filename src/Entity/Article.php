<?php

declare(strict_types=1);

namespace BEAR\Kata\Entity;

use function array_column;
use function implode;
use function mb_substr;
use function strip_tags;
use function substr;
use function trim;

/** @SuppressWarnings("PHPMD.ExcessiveParameterList") */
final readonly class Article
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $body,
        public string|null $excerpt,
        public ArticleStatus $status,
        public string|null $publishedAt,
        public int $authorId,
        public int $categoryId,
    ) {
    }

    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === ArticleStatus::Draft;
    }

    public function belongsToCategory(int $categoryId): bool
    {
        return $this->categoryId === $categoryId;
    }

    public function isWrittenBy(int $authorId): bool
    {
        return $this->authorId === $authorId;
    }

    public function statusClass(): string
    {
        return $this->isPublished() ? 'is-published' : 'is-draft';
    }

    public function publishedAtLabel(): string|null
    {
        if (! $this->isPublished() || $this->publishedAt === null) {
            return null;
        }

        return substr($this->publishedAt, 0, 10);
    }

    public function summary(): string|null
    {
        if ($this->excerpt !== null && $this->excerpt !== '') {
            return $this->excerpt;
        }

        $plain = trim(strip_tags($this->body));

        return $plain === '' ? null : mb_substr($plain, 0, 120) . '…';
    }

    public function url(): string
    {
        return '/article?id=' . $this->id;
    }

    /** @param list<array{id: int, slug: string, name: string}> $tags */
    public function tagsJoined(array $tags): string
    {
        return implode(', ', array_column($tags, 'name'));
    }
}
