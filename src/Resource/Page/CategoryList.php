<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Query\CategoryQueryInterface;

class CategoryList extends ResourceObject
{
    /** @var array{categories: list<Category>} */
    public $body;

    public function __construct(
        private readonly CategoryQueryInterface $category,
    ) {
    }

    public function onGet(): static
    {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = ['categories' => $this->category->list()];

        return $this;
    }
}
