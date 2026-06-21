<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Query\TagCommandInterface;
use BEAR\Kata\Query\TagQueryInterface;

/**
 * Cache showcase leaf — symmetric to {@see Author}.
 *
 * Zero cache-related primitives in this class. `#[Cacheable]` is the entire
 * cache surface; the framework adds the self URI tag and writes are
 * auto-purged by `RefreshSameCommand`. PUT here is the trigger for the
 * `Cache\ArticleTags` invalidation demo.
 */
#[Alps('Tag')]
#[Cacheable]
class Tag extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly TagCommandInterface $tagCmd,
    ) {
    }

    #[Alps('goCacheTag')]
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

    #[Alps('doUpdateCacheTag')]
    #[JsonSchema(schema: 'write_response.json', params: 'tag_update.json')]
    public function onPut(int $id, string $slug, string $name): static
    {
        if ($this->tag->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Tag not found', 'id' => $id];

            return $this;
        }

        $this->tagCmd->update($id, $slug, $name);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }
}
