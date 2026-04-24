<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Category;
use Ray\MediaQuery\Annotation\DbQuery;

interface CategoryQueryInterface
{
    #[DbQuery('get_category', type: 'row')]
    public function get(int $id): Category|null;

    /** @return list<Category> */
    #[DbQuery('list_categories', type: 'row_list')]
    public function list(): array;
}
