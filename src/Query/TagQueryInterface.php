<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use BEAR\Examples\Entity\Tag;
use Ray\MediaQuery\Annotation\DbQuery;

interface TagQueryInterface
{
    #[DbQuery('tag_item')]
    public function item(int $id): Tag|null;

    #[DbQuery('tag_by_slug')]
    public function bySlug(string $slug): Tag|null;

    /** @return list<Tag> */
    #[DbQuery('tag_list')]
    public function list(): array;

    /** @return list<Tag> */
    #[DbQuery('tag_list_by_article')]
    public function listByArticle(int $articleId): array;

    /**
     * Batch read used by the crawl/DataLoader companion.
     *
     * @param list<int> $articleIds
     *
     * @return list<array{articleId: int, id: int, slug: string, name: string}>
     */
    #[DbQuery('tag_list_by_articles')]
    public function listByArticles(array $articleIds): array;
}
