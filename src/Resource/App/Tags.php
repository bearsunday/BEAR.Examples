<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;
use function count;

class Tags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tagQuery,
    ) {
    }

    #[Link(rel: 'goTag', href: 'app://self/tag{?id}')]
    #[JsonSchema('tagList.json')]
    public function onGet(int|null $articleId = null): static
    {
        $items = $articleId === null
            ? $this->tagQuery->list()
            : $this->tagQuery->listByArticle($articleId);

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
