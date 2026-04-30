<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query\Variations;

use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleAsArrayQueryInterface
{
    /** @return array{id: int, slug: string, title: string, body: string, excerpt: ?string, status: string, publishedAt: ?string, authorId: int, categoryId: int}|null */
    #[DbQuery('article_as_array_item', type: 'row')]
    public function item(int $id): array|null;
}
