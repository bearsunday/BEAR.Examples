<?php

declare(strict_types=1);

namespace MyVendor\Cms\Factory;

use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Service\MarkdownRendererInterface;

/**
 * Builds Article entities with the MarkdownRendererInterface injected.
 *
 * Wired via #[DbQuery(factory: ArticleFactory::class)] on the Read-side
 * methods of ArticleQueryInterface. Ray.MediaQuery's FetchInjectionFactory
 * calls ::factory(...$columns) once per row using PDO::FETCH_FUNC, with
 * SELECT column order matching the parameter order below.
 */
final readonly class ArticleFactory
{
    public function __construct(
        private MarkdownRendererInterface $renderer,
    ) {
    }

    public function factory(
        int $id,
        string $slug,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
        int $authorId,
        int $categoryId,
    ): Article {
        return new Article(
            id: $id,
            slug: $slug,
            title: $title,
            body: $body,
            excerpt: $excerpt,
            status: $status,
            publishedAt: $publishedAt,
            authorId: $authorId,
            categoryId: $categoryId,
            renderer: $this->renderer,
        );
    }
}
