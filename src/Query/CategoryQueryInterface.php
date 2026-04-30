<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Category;
use Ray\MediaQuery\Annotation\DbQuery;

interface CategoryQueryInterface
{
    #[DbQuery('category_item')]
    public function item(int $id): Category|null;

    #[DbQuery('category_by_slug')]
    public function bySlug(string $slug): Category|null;

    /** @return list<Category> */
    #[DbQuery('category_list')]
    public function list(): array;
}
