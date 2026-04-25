<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Factory\ArticleFactory;
use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleQueryInterface
{
    #[DbQuery('article_by_id', type: 'row', factory: ArticleFactory::class)]
    public function getById(int $id): Article|null;

    #[DbQuery('article_by_slug', type: 'row', factory: ArticleFactory::class)]
    public function getBySlug(string $slug): Article|null;

    /** @return list<Article> */
    #[DbQuery('article_list', type: 'row_list', factory: ArticleFactory::class)]
    public function list(
        int|null $categoryId = null,
        int|null $tagId = null,
        string|null $status = null,
        int $limit = 20,
        int $offset = 0,
    ): array;
}
