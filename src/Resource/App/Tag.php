<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagCommandInterface;
use MyVendor\Cms\Query\TagQueryInterface;

class Tag extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly TagCommandInterface $tagCmd,
    ) {
    }

    #[Link(rel: 'goTagList', href: 'app://self/tags')]
    #[Link(rel: 'goArticleList', href: 'app://self/articles{?tagId}')]
    #[JsonSchema('tag.json')]
    public function onGet(int $id): static
    {
        $tag = $this->tag->item($id);
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

    #[JsonSchema(schema: 'write_response.json', params: 'tag_create.json')]
    public function onPost(string $slug, string $name): static
    {
        $this->tagCmd->add($slug, $name);
        $created = $this->tag->bySlug($slug);
        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/tag?id=' . $created->id : '/tag';
        $this->body = ['id' => $created?->id, 'slug' => $slug];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->tag->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Tag not found', 'id' => $id];

            return $this;
        }

        $this->tagCmd->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
