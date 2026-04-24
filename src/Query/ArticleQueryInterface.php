<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Article;
use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleQueryInterface
{
    #[DbQuery('get_article', type: 'row')]
    public function get(int $id): Article|null;

    /** @return list<Article> */
    #[DbQuery('list_articles', type: 'row_list')]
    public function list(
        int|null $categoryId = null,
        int|null $tagId = null,
        string|null $status = null,
        int $limit = 20,
        int $offset = 0,
    ): array;
}
