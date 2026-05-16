<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\ResourceObject;

/**
 * Cache showcase parent — single child dependency.
 *
 * `#[Embed]` handles the composition (the child resource is materialized into
 * `_embedded.author` by `HalRenderer`). Cross-resource invalidation is
 * automatic: `QueryRepository::setCacheDependency` walks the body before HAL
 * mutates it and merges the child's Surrogate-Key into the parent.
 */
#[Alps('AuthorProfile')]
#[Cacheable]
class AuthorProfile extends ResourceObject
{
    #[Alps('goAuthorProfile')]
    #[Embed(rel: 'author', src: 'app://self/cache/author')]
    #[JsonSchema('author_profile.json')]
    public function onGet(int $authorId): static
    {
        $this->body['author']->addQuery(['id' => $authorId]);

        $this->body += [
            'authorId' => $authorId,
            'dependencyUri' => 'app://self/cache/author?id=' . $authorId,
            'cachePattern' => 'Cacheable + Embed (auto dependency)',
        ];

        return $this;
    }
}
