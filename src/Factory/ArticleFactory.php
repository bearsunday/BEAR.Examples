<?php

declare(strict_types=1);

namespace MyVendor\Cms\Factory;

use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\ArticleStatus;

use function array_map;
use function str_contains;
use function str_replace;

/**
 * Builds Article entities from the database.
 *
 * Wired via #[DbQuery(factory: ArticleFactory::class)] on single-row
 * ArticleQueryInterface methods. Paged list queries return associative rows
 * from Ray.MediaQuery's Pager path and are mapped through fromRows().
 */
final readonly class ArticleFactory
{
    /** @SuppressWarnings("PHPMD.StaticAccess") enum value reconstruction */
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
     * @param list<array<string, mixed>> $rows
     *
     * @return list<Article>
     */
    public function fromRows(array $rows): array
    {
        return array_map(fn (array $row): Article => $this->fromRow($row), $rows);
    }

    /** @param array<string, mixed> $row */
    public function fromRow(array $row): Article
    {
        return $this->factory(
            (int) $row['id'],
            (string) $row['slug'],
            (string) $row['title'],
            (string) $row['body'],
            isset($row['excerpt']) ? (string) $row['excerpt'] : null,
            (string) $row['status'],
            $row['published_at'],
            (int) $row['author_id'],
            (int) $row['category_id'],
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
