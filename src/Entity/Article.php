<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

use MyVendor\Cms\Service\MarkdownRendererInterface;
use RuntimeException;

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
        /**
         * Injected by ArticleFactory in production. Null in pure unit tests
         * where the entity is constructed without a Markdown service.
         */
        private MarkdownRendererInterface|null $renderer = null,
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

    /**
     * Render the body Markdown to HTML using the injected renderer.
     *
     * Demonstrates BDR's Domain layer carrying its own infrastructure
     * dependency (a service) rather than acting as a pure DTO.
     */
    public function renderHtml(): string
    {
        if ($this->renderer === null) {
            throw new RuntimeException('Article was constructed without a MarkdownRendererInterface — call via ArticleFactory or pass one explicitly.');
        }

        return $this->renderer->render($this->body);
    }
}
