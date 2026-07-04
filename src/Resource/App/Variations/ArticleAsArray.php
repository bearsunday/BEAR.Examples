<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Variations;

use BEAR\Kata\Query\Variations\ArticleAsArrayQueryInterface;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function preg_match;
use function str_replace;

/**
 * Comparison-only Article GET. Canonical equivalent: App\Article::onGet().
 *
 * Mainline keeps the Article entity because predicates and injected rendering
 * behaviour belong in the domain object, not in arrays spread across resources.
 *
 * @psalm-type ArticleBody = array{
 *     id: int,
 *     slug: string,
 *     title: string,
 *     body: string,
 *     excerpt: ?string,
 *     status: string,
 *     publishedAt: ?string,
 *     authorId: int,
 *     categoryId: int
 * }
 */
class ArticleAsArray extends ResourceObject
{
    public function __construct(
        private readonly ArticleAsArrayQueryInterface $article,
    ) {
    }

    #[JsonSchema('article.json')]
    public function onGet(int $id): static
    {
        $row = $this->article->item($id);
        if ($row === null) {
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
