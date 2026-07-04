<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Kata\Query\TagQueryInterface;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

use function array_map;
use function count;

#[Alps('TagList')]
class Tags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
    ) {
    }

    #[Alps('goTagList')]
    #[Link(rel: 'goTag', href: 'app://self/tag{?id}')]
    #[JsonSchema('tagList.json')]
    public function onGet(int|null $articleId = null): static
    {
        $items = $articleId === null
            ? $this->tag->list()
            : $this->tag->listByArticle($articleId);

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
