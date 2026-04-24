<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;

class Tags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tagQuery,
    ) {
    }

    #[Link(rel: 'tag', href: 'app://self/tag{?id}')]
    public function onGet(): static
    {
        $items = $this->tagQuery->list();
        $this->body = [
            'items' => array_map(static fn ($t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => $t->name,
            ], $items),
            'count' => count($items),
        ];

        return $this;
    }
}
