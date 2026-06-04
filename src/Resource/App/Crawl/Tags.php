<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Crawl;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;

class Tags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
    ) {
    }

    #[JsonSchema('crawl_tag_list.json')]
    public function onGet(int $articleId): static
    {
        $tags = $this->tag->listByArticle($articleId);

        $this->body = array_map(static fn ($tag): array => [
            'articleId' => $articleId,
            'id' => $tag->id,
            'slug' => $tag->slug,
            'name' => $tag->name,
        ], $tags);

        return $this;
    }
}
