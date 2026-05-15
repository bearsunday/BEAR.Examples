<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\QueryRepository\Header;
use BEAR\QueryRepository\UriTagInterface;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\ResourceObject;

/**
 * Cache showcase parent — single child dependency.
 *
 * `#[Embed]` handles the composition (the child resource is materialized into
 * `_embedded.author` by `HalRenderer`), but the HAL renderer also moves the
 * embed request out of `$ro->body` into `_embedded` before
 * `EtagSetter::setCacheDependency` walks the body, so the auto-merge of the
 * child's Surrogate-Key into the parent's never fires. Cross-resource
 * invalidation therefore requires one line of manual code: a `fromAssoc`
 * mapping the dependency to a Surrogate-Key tag, exactly like the N-child
 * `Cache\ArticleTags` case. See `docs/conventions.md` § "Cross-resource cache
 * dependency: one `fromAssoc` line".
 */
#[Alps('AuthorProfile')]
#[Cacheable]
class AuthorProfile extends ResourceObject
{
    public function __construct(
        private readonly UriTagInterface $uriTag,
    ) {
    }

    #[Alps('goAuthorProfile')]
    #[Embed(rel: 'author', src: 'app://self/cache/author')]
    #[JsonSchema('author_profile.json')]
    public function onGet(int $authorId): static
    {
        $this->body['author']->addQuery(['id' => $authorId]);

        // One line of manual cache code: declare the cross-resource dependency
        // so that PUT app://self/cache/author?id={authorId} cascades into this
        // response's invalidation set.
        $this->headers[Header::SURROGATE_KEY] = $this->uriTag->fromAssoc(
            'app://self/cache/author{?id}',
            [['id' => $authorId]],
        );

        $this->body += [
            'authorId' => $authorId,
            'dependencyUri' => 'app://self/cache/author?id=' . $authorId,
            'cachePattern' => 'Cacheable + Embed + fromAssoc (single dependency)',
        ];

        return $this;
    }
}
