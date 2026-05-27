<?php

declare(strict_types=1);

namespace MyVendor\Cms\Result;

use ArrayIterator;
use Countable;
use DateTimeImmutable;
use Generator;
use IteratorAggregate;
use MyVendor\Cms\Entity\Article;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

use function array_map;
use function count;
use function max;

/**
 * SELECT result wrapper for Ray.MediaQuery 1.1's PostQueryInterface path.
 *
 * @implements IteratorAggregate<int, Article>
 */
final readonly class ArticleSelection implements PostQueryInterface, IteratorAggregate, Countable
{
    /** @param list<Article> $rows */
    public function __construct(
        public array $rows,
    ) {
    }

    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<Article> $rows */
        $rows = $context->rows;

        return new self($rows);
    }

    /** @return Generator<int, Article> */
    public function published(): Generator
    {
        foreach ($this->rows as $article) {
            if (! $article->isPublished()) {
                continue;
            }

            yield $article;
        }
    }

    /** @return Generator<int, ArticleFeedItem> */
    public function feed(DateTimeImmutable|null $now = null): Generator
    {
        $now ??= new DateTimeImmutable();

        foreach ($this->published() as $article) {
            $item = $this->toFeedItem($article, $now);
            if ($item === null) {
                continue;
            }

            yield $item;
        }
    }

    private function toFeedItem(Article $article, DateTimeImmutable $now): ArticleFeedItem|null
    {
        if ($article->publishedAt === null) {
            return null;
        }

        $summary = $article->summary();
        if ($summary === null) {
            return null;
        }

        $publishedAt = new DateTimeImmutable($article->publishedAt);

        return new ArticleFeedItem(
            id: $article->id,
            url: $article->url(),
            title: $article->title,
            summary: $summary,
            publishedAt: $article->publishedAt,
            publishedAtLabel: $publishedAt->format('Y-m-d'),
            postedAgoLabel: $this->postedAgoLabel($publishedAt, $now),
        );
    }

    private function postedAgoLabel(DateTimeImmutable $publishedAt, DateTimeImmutable $now): string
    {
        $seconds = max(0, $now->getTimestamp() - $publishedAt->getTimestamp());
        if ($seconds < 60) {
            return 'just now';
        }

        if ($seconds < 3600) {
            return $this->unitLabel((int) ($seconds / 60), 'minute');
        }

        if ($seconds < 86400) {
            return $this->unitLabel((int) ($seconds / 3600), 'hour');
        }

        return $this->unitLabel((int) ($seconds / 86400), 'day');
    }

    private function unitLabel(int $count, string $unit): string
    {
        return $count . ' ' . $unit . ($count === 1 ? '' : 's') . ' ago';
    }

    /** @return list<string> */
    public function titles(): array
    {
        return array_map(static fn (Article $article): string => $article->title, $this->rows);
    }

    public function first(): Article|null
    {
        return $this->rows[0] ?? null;
    }

    public function count(): int
    {
        return count($this->rows);
    }

    /** @return ArrayIterator<int, Article> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rows);
    }
}
