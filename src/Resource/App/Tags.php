<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagQueryInterface;

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
    #[Cli(name: 'tag-list', description: 'List tags (optionally for one article)', output: 'count')]
    public function onGet(
        #[Option(shortName: 'a', description: 'Filter by article id')]
        int|null $articleId = null,
    ): static {
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
