<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use Aura\Sql\ExtendedPdoInterface;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function str_contains;
use function str_replace;

/**
 * Comparison-only Article GET. Canonical equivalent: App\Article::onGet().
 *
 * Mainline keeps MediaQuery because it externalises SQL and centralises
 * parameter conversion, fetch strategy, logging, and SQL-file discovery.
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
