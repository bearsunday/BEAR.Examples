<?php

declare(strict_types=1);

namespace BEAR\Kata\Result;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use BEAR\Kata\Entity\Article;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;

use function array_filter;
use function array_map;
use function array_values;
use function count;

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

    public function published(): self
    {
        return new self(array_values(array_filter(
            $this->rows,
            static fn (Article $article): bool => $article->isPublished(),
        )));
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
