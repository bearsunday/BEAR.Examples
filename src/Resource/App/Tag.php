<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Command\TagCommandInterface;
use MyVendor\Cms\Query\TagQueryInterface;

class Tag extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tagQuery,
        private readonly TagCommandInterface $tagCommand,
    ) {
    }

    #[Link(rel: 'tags', href: 'app://self/tags')]
    #[Link(rel: 'articles', href: 'app://self/articles{?tagId}')]
    public function onGet(int $id): static
    {
        $tag = $this->tagQuery->getById($id);
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

    public function onPost(string $slug, string $name): static
    {
        $this->tagCommand->add(slug: $slug, name: $name);
        $created = $this->tagQuery->getBySlug($slug);
        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/tag?id=' . $created->id : '/tag';
        $this->body = ['id' => $created?->id, 'slug' => $slug];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->tagQuery->getById($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Tag not found', 'id' => $id];

            return $this;
        }

        $this->tagCommand->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
