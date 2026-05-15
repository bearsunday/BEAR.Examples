<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\QueryRepository\Header;
use BEAR\QueryRepository\UriTagInterface;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;
use function count;

/**
 * Cache showcase exception — body-derived variable-length dependency set.
 *
 * `#[Embed]` cannot statically express "depend on N tag URIs where N comes
 * from the database row for this article", so this is the one place where
 * `UriTagInterface::fromAssoc` is structurally required. See
 * `docs/conventions.md` § "Exception — UriTagInterface::fromAssoc for
 * body-variable dependencies".
 *
 * Exactly one line of user-written cache code (the Surrogate-Key assignment)
 * is required, and `ArticleTagsCacheTest::testSourceHasExactlyOneFromAssocCall`
 * pins that invariant.
 */
#[Alps('ArticleTags')]
#[Cacheable]
class ArticleTags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly UriTagInterface $uriTag,
    ) {
    }

    #[Alps('goCacheArticleTags')]
    #[JsonSchema('cache_article_tags.json')]
    public function onGet(int $articleId): static
    {
        $items = array_map(
            static fn ($t) => ['id' => $t->id, 'slug' => $t->slug, 'name' => $t->name],
            $this->tag->listByArticle($articleId),
        );

        // The only manual cache primitive in the showcase: map a body-derived
        // variable-length set of dependencies to a Surrogate-Key. When the
        // article has no tags, fromAssoc returns '' — leave the header unset
        // so Symfony's tag-aware cache adapter doesn't reject the empty tag.
        if ($items !== []) {
            $this->headers[Header::SURROGATE_KEY] = $this->uriTag->fromAssoc('app://self/cache/tag{?id}', $items);
        }

        $this->body = [
            'articleId' => $articleId,
            'cachePattern' => 'Cacheable + UriTagInterface::fromAssoc',
            'items' => $items,
            'count' => count($items),
        ];

        return $this;
    }
}
