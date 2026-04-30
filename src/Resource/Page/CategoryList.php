<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Query\CategoryQueryInterface;

/** @property array{categories: list<Category>} $body */
class CategoryList extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $category,
    ) {
    }

    public function onGet(): static
    {
        $this->body = ['categories' => $this->category->list()];

        return $this;
    }
}
