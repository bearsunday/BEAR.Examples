<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\CategoryQueryInterface;

class Category extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $categoryQuery,
    ) {
    }

    #[Link(rel: 'categories', href: 'app://self/categories')]
    #[Link(rel: 'articles', href: 'app://self/articles{?categoryId}')]
    public function onGet(int $id): static
    {
        $category = $this->categoryQuery->get($id);
        if ($category === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Category not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $category->id,
            'slug' => $category->slug,
            'name' => $category->name,
            'description' => $category->description,
            'parentId' => $category->parentId,
        ];

        return $this;
    }
}
