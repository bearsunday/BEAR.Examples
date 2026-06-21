<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Query\AuthorQueryInterface;

/**
 * Cache showcase parent — single child dependency.
 *
 * `#[Embed]` handles the composition (the child resource is materialized into
 * `_embedded.author` by `HalRenderer`). Cross-resource invalidation is
 * automatic: `QueryRepository::setCacheDependency` walks the body before HAL
 * mutates it and merges the child's Surrogate-Key into the parent.
 *
 * Existence of `authorId` is validated up-front: a missing author returns 404
 * with the Embed Request dropped by replacing `$this->body`. `CacheInterceptor`
 * only stores responses with code 200 (it purges on anything else), so a 404
 * here cannot end up as a stale cache entry.
 */
#[Alps('AuthorProfile')]
#[Cacheable]
class AuthorProfile extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    #[Alps('goAuthorProfile')]
    #[Embed(rel: 'author', src: 'app://self/cache/author')]
    #[JsonSchema('author_profile.json')]
    public function onGet(int $authorId): static
    {
        if ($this->author->item($authorId) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $authorId];

            return $this;
        }

        $this->body['author']->addQuery(['id' => $authorId]);

        $this->body += [
            'authorId' => $authorId,
            'dependencyUri' => 'app://self/cache/author?id=' . $authorId,
            'cachePattern' => 'Cacheable + Embed (auto dependency)',
        ];

        return $this;
    }
}
