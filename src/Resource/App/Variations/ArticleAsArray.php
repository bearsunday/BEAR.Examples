<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\MediaQuery\SqlQueryInterface;

use function get_object_vars;
use function is_array;
use function str_contains;
use function str_replace;

/**
 * Comparison-only Article GET. Canonical equivalent: App\Article::onGet().
 *
 * Mainline keeps the Article entity because predicates and injected rendering
 * behaviour belong in the domain object, not in arrays spread across resources.
 */
class ArticleAsArray extends ResourceObject
{
    public function __construct(
        private readonly SqlQueryInterface $sqlQuery,
    ) {
    }

    #[JsonSchema('article.json')]
    public function onGet(int $id): static
    {
        $row = $this->sqlQuery->getRow('article_as_array_item', ['id' => $id]);
        if ($row === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        /** @var array<string, mixed> $row */
        $row = is_array($row) ? $row : get_object_vars($row);
        $this->body = self::articleBody($row);

        return $this;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{id: int, slug: string, title: string, body: string, excerpt: ?string, status: string, publishedAt: ?string, authorId: int, categoryId: int}
     */
    private static function articleBody(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'excerpt' => isset($row['excerpt']) ? (string) $row['excerpt'] : null,
            'status' => (string) $row['status'],
            'publishedAt' => self::normaliseDateTime($row['publishedAt'] ?? null),
            'authorId' => (int) $row['authorId'],
            'categoryId' => (int) $row['categoryId'],
        ];
    }

    private static function normaliseDateTime(mixed $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        return str_contains($value, 'T') ? $value : str_replace(' ', 'T', $value) . 'Z';
    }
}
