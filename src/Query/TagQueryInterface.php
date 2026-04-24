<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Tag;
use Ray\MediaQuery\Annotation\DbQuery;

interface TagQueryInterface
{
    #[DbQuery('get_tag', type: 'row')]
    public function get(int $id): Tag|null;

    /** @return list<Tag> */
    #[DbQuery('list_tags', type: 'row_list')]
    public function list(): array;

    /** @return list<Tag> */
    #[DbQuery('list_tags_by_article', type: 'row_list')]
    public function listByArticle(int $articleId): array;
}
