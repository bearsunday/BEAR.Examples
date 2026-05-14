<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagCommandInterface;
use MyVendor\Cms\Query\TagQueryInterface;

use function assert;

#[Alps('Tag')]
class Tag extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly TagCommandInterface $tagCmd,
    ) {
    }

    #[Alps('goTag')]
    #[Link(rel: 'goTagList', href: 'app://self/tags')]
    #[Link(rel: 'goArticleList', href: 'app://self/articles{?tagId}')]
    #[JsonSchema('tag.json')]
    #[Cli(name: 'tag-show', description: 'Show a tag by id', output: 'name')]
    public function onGet(
        #[Option(shortName: 'i', description: 'Tag id')]
        int $id,
    ): static {
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

    #[Alps('doCreateTag')]
    #[JsonSchema(schema: 'write_response.json', params: 'tag_create.json')]
    #[Cli(name: 'tag-add', description: 'Create a new tag')]
    public function onPost(
        #[Option(shortName: 's', description: 'Tag slug')]
        string $slug,
        #[Option(shortName: 'n', description: 'Tag name')]
        string $name,
    ): static {
        $this->tagCmd->add($slug, $name);
        // bySlug after add is invariant per docs/conventions.md §4.
        $created = $this->tag->bySlug($slug);
        assert($created !== null);
        $this->code = Code::CREATED;
        $this->headers['Location'] = '/tag?id=' . $created->id;
        $this->body = ['id' => $created->id, 'slug' => $slug];

        return $this;
    }

    #[Alps('doDeleteTag')]
    #[Cli(name: 'tag-delete', description: 'Delete a tag')]
    public function onDelete(
        #[Option(shortName: 'i', description: 'Tag id')]
        int $id,
    ): static {
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
