<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Kata\Entity\Category;
use BEAR\Kata\Query\CategoryQueryInterface;
use BEAR\Resource\ResourceObject;

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
