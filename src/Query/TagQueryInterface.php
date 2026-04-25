<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Tag;
use Ray\MediaQuery\Annotation\DbQuery;

interface TagQueryInterface
{
    #[DbQuery('tag_by_id', type: 'row')]
    public function getById(int $id): Tag|null;

    #[DbQuery('tag_by_slug', type: 'row')]
    public function getBySlug(string $slug): Tag|null;

    /** @return list<Tag> */
    #[DbQuery('tag_list', type: 'row_list')]
    public function list(): array;

    /** @return list<Tag> */
    #[DbQuery('tag_list_by_article', type: 'row_list')]
    public function listByArticle(int $articleId): array;
}
