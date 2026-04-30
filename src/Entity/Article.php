<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

use MyVendor\Cms\Exception\MissingMarkdownRendererException;
use MyVendor\Cms\Service\MarkdownRendererInterface;
use MyVendor\Cms\ViewEntity\HtmlString;

use function array_column;
use function implode;
use function mb_substr;
use function strip_tags;
use function substr;
use function trim;

/** @SuppressWarnings("PHPMD.ExcessiveParameterList") */
final readonly class Article
{
    public const string STATUS_DRAFT = 'draft';
    public const string STATUS_PUBLISHED = 'published';

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
        public MarkdownRendererInterface|null $renderer = null,
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

    public function bodyHtml(): HtmlString
    {
        return new HtmlString($this->renderHtml());
    }

    /** @param list<array{id: int, slug: string, name: string}> $tags */
    public function tagsJoined(array $tags): string
    {
        return implode(', ', array_column($tags, 'name'));
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
            throw new MissingMarkdownRendererException('Article was constructed without a MarkdownRendererInterface — call via ArticleFactory or pass one explicitly.');
        }

        return $this->renderer->render($this->body);
    }
}
