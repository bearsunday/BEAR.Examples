<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\CacheableResponse;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Query\CategoryQueryInterface;

use function array_map;
use function count;

#[Alps('CategoryList')]
#[CacheableResponse]
class Categories extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $category,
    ) {
    }

    #[Alps('goCategoryList')]
    #[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
    #[JsonSchema('categoryList.json')]
    public function onGet(): static
    {
        $items = $this->category->list();
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
