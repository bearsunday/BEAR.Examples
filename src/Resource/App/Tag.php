<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagQueryInterface;

class Tag extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tagQuery,
    ) {
    }

    #[Link(rel: 'tags', href: 'app://self/tags')]
    #[Link(rel: 'articles', href: 'app://self/articles{?tagId}')]
    public function onGet(int $id): static
    {
        $tag = $this->tagQuery->get($id);
        if ($tag === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Tag not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $tag->id,
            'slug' => $tag->slug,
            'name' => $tag->name,
        ];

        return $this;
    }
}
