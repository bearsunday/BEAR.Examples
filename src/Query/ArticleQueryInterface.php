<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Factory\ArticleFactory;
use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Annotation\Pager;
use Ray\MediaQuery\PagesInterface;

interface ArticleQueryInterface
{
    #[DbQuery('article_item', factory: ArticleFactory::class)]
    public function item(int $id): Article|null;

    #[DbQuery('article_by_slug', factory: ArticleFactory::class)]
    public function bySlug(string $slug): Article|null;

    #[DbQuery('article_list')]
    #[Pager(perPage: 'perPage')]
    public function list(
        int|null $categoryId = null,
        int|null $tagId = null,
        int|null $authorId = null,
        string|null $status = null,
        int $perPage = 20,
    ): PagesInterface;
}
