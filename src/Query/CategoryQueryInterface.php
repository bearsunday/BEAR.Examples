<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Category;
use Ray\MediaQuery\Annotation\DbQuery;

interface CategoryQueryInterface
{
    #[DbQuery('category_by_id', type: 'row')]
    public function getById(int $id): Category|null;

    #[DbQuery('category_by_slug', type: 'row')]
    public function getBySlug(string $slug): Category|null;

    /** @return list<Category> */
    #[DbQuery('category_list', type: 'row_list')]
    public function list(): array;
}
