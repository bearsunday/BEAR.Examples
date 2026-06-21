<?php

declare(strict_types=1);

namespace BEAR\Kata\DataLoader;

use BEAR\Resource\DataLoader\DataLoaderInterface;
use BEAR\Kata\Query\TagQueryInterface;

use function array_values;

/**
 * Batch-loads tag rows for the `app://self/crawl/articles` linkCrawl graph.
 */
final readonly class ArticleTagsDataLoader implements DataLoaderInterface
{
    public function __construct(
        private TagQueryInterface $tag,
    ) {
    }

    /**
     * @param list<array<string, string>> $queries
     *
     * @return list<array{articleId: int, id: int, slug: string, name: string}>
     */
    public function __invoke(array $queries): array
    {
        $articleIds = [];
        foreach ($queries as $query) {
            if (! isset($query['articleId'])) {
                continue;
            }

            $articleIds[(int) $query['articleId']] = (int) $query['articleId'];
        }

        if ($articleIds === []) {
            return [];
        }

        return $this->tag->listByArticles(array_values($articleIds));
    }
}
