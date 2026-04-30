<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function preg_match;
use function str_replace;

/**
 * Comparison-only Article GET. Canonical equivalent: App\Article::onGet().
 *
 * Mainline keeps MediaQuery because it externalises SQL and centralises
 * parameter conversion, fetch strategy, logging, and SQL-file discovery.
 *
 * @psalm-import-type ArticleBody from ArticleAsArray
 */
class ArticleRawPdo extends ResourceObject
{
    private const string SQL = <<<'SQL'
SELECT
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at AS publishedAt,
    author_id AS authorId,
    category_id AS categoryId
FROM articles
WHERE id = :id
LIMIT 1
SQL;

    public function __construct(
        private readonly ExtendedPdoInterface $pdo,
    ) {
    }

    #[JsonSchema('article.json')]
    public function onGet(int $id): static
    {
        $row = $this->pdo->fetchOne(self::SQL, ['id' => $id]);
        if ($row === false) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->body = $this->toResponseBody($row);

        return $this;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @psalm-return ArticleBody
     */
    private function toResponseBody(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'title' => (string) $row['title'],
            'body' => (string) $row['body'],
            'excerpt' => isset($row['excerpt']) ? (string) $row['excerpt'] : null,
            'status' => (string) $row['status'],
            'publishedAt' => $this->normaliseDateTime($row['publishedAt'] ?? null),
            'authorId' => (int) $row['authorId'],
            'categoryId' => (int) $row['categoryId'],
        ];
    }

    private function normaliseDateTime(mixed $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = (string) $value;

        $value = str_replace(' ', 'T', $value);
        if (preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/i', $value) === 1) {
            return $value;
        }

        return $value . 'Z';
    }
}
