<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Variations;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\MediaQuery\SqlQueryInterface;

use function assert;
use function ceil;
use function is_array;
use function max;
use function preg_match;
use function str_replace;
use function str_word_count;

/**
 * Comparison-only Article GET. Canonical equivalent: App\Article::onGet().
 *
 * Mainline keeps declarative #[DbQuery] for simple reads; inject SqlQuery only
 * when a resource needs PHP post-processing or multi-query orchestration.
 */
class ArticleSqlQuery extends ResourceObject
{
    public function __construct(
        private readonly SqlQueryInterface $sqlQuery,
    ) {
    }

    #[JsonSchema('article.json')]
    public function onGet(int $id): static
    {
        $row = $this->row('article_sqlquery_item', ['id' => $id]);
        if ($row === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $body = self::articleBody($row);
        $body['readingTimeMinutes'] = self::readingTimeMinutes($body['body']);
        $body['previous'] = null;
        $body['next'] = null;

        $publishedAt = $row['publishedAt'] ?? null;
        if ($publishedAt !== null && $publishedAt !== '') {
            $params = ['id' => $body['id'], 'publishedAt' => $publishedAt];
            $body['previous'] = self::articleSummary($this->row('article_sqlquery_previous', $params));
            $body['next'] = self::articleSummary($this->row('article_sqlquery_next', $params));
        }

        $this->body = $body;

        return $this;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>|null
     */
    private function row(string $sqlId, array $values): array|null
    {
        $row = $this->sqlQuery->getRow($sqlId, $values);
        assert($row === null || is_array($row));

        return $row;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array{id: int, slug: string, title: string, body: string, excerpt: ?string, status: string, publishedAt: ?string, authorId: int, categoryId: int, readingTimeMinutes?: int, previous?: ?array{id: int, slug: string, title: string, publishedAt: ?string}, next?: ?array{id: int, slug: string, title: string, publishedAt: ?string}}
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

    /**
     * @param array<string, mixed>|null $row
     *
     * @return array{id: int, slug: string, title: string, publishedAt: ?string}|null
     */
    private static function articleSummary(array|null $row): array|null
    {
        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'slug' => (string) $row['slug'],
            'title' => (string) $row['title'],
            'publishedAt' => self::normaliseDateTime($row['publishedAt'] ?? null),
        ];
    }

    private static function readingTimeMinutes(string $body): int
    {
        return max(1, (int) ceil(str_word_count($body) / 200));
    }

    private static function normaliseDateTime(mixed $value): string|null
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
