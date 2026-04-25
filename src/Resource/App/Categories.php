<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\CategoryQueryInterface;

use function array_map;
use function count;

class Categories extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $categoryQuery,
    ) {
    }

    #[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
    #[JsonSchema('categoryList.json')]
    public function onGet(): static
    {
        $items = $this->categoryQuery->list();
        $this->body = [
            'items' => array_map(static fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'description' => $c->description,
                'parentId' => $c->parentId,
            ], $items),
            'count' => count($items),
        ];

        return $this;
    }
}
