<?php

declare(strict_types=1);

namespace MyVendor\Cms\Factory;

use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\ArticleStatus;

use function str_contains;
use function str_replace;

/**
 * Builds Article entities from the database.
 *
 * Wired via #[DbQuery(factory: ArticleFactory::class)] on the Read-side
 * methods of ArticleQueryInterface. Ray.MediaQuery's FetchInjectionFactory
 * calls ::factory(...$columns) once per row using PDO::FETCH_FUNC, with
 * SELECT column order matching the parameter order below.
 */
final readonly class ArticleFactory
{
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
            $id,
            $slug,
            $title,
            $body,
            $excerpt,
            ArticleStatus::from($status),
            self::normaliseDateTime($publishedAt),
            $authorId,
            $categoryId,
        );
    }

    /**
     * Normalise a database datetime (`YYYY-MM-DD HH:MM:SS`) to RFC3339
     * (`YYYY-MM-DDTHH:MM:SSZ`) so the JSON Schema `format: date-time`
     * validation passes on both Read paths (Fake already emits RFC3339).
     */
    private static function normaliseDateTime(string|null $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (str_contains($value, 'T')) {
            return $value;
        }

        return str_replace(' ', 'T', $value) . 'Z';
    }
}
