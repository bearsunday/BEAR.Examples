<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\QueryRepository\Header;
use BEAR\QueryRepository\UriTagInterface;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\ArticleTagCommandInterface;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;
use function count;

/**
 * Cache showcase exception — body-derived variable-length dependency set.
 *
 * `#[Embed]` cannot statically express "depend on N tag URIs where N comes
 * from the database row for this article", so this is the one place where
 * `UriTagInterface::fromAssoc` is structurally required. See
 * `docs/conventions.md` § "Cross-resource dependency — exactly one `fromAssoc`
 * line".
 *
 * Exactly one line of user-written cache code (the Surrogate-Key assignment)
 * is required, and `ArticleTagsCacheTest::testSourceHasExactlyOneFromAssocCall`
 * pins that invariant.
 *
 * Invalidation surface:
 * - `PUT app://self/cache/tag?id={tagId}` — any tag whose URI is in the
 *   current dependency set cascades through the Surrogate-Key.
 * - `PUT app://self/cache/articletags?articleId={id}` — `RefreshSameCommand`
 *   (the default `Commands` for `#[Cacheable]`) purges the self URI tag, so
 *   the next GET re-queries and rebuilds the dependency set.
 *
 * Out of scope (intentional): writes to the main `app://self/article`
 * resource that change `tagIds` are NOT wired into this cache's invalidation.
 * The showcase deliberately keeps the cache surface self-contained — adding
 * cross-resource purges from the main Article path would couple it to the
 * showcase and obscure the canonical patterns. A production CMS that needs
 * this would add an explicit `DonutRepositoryInterface::purge()` call (or an
 * equivalent tag invalidation) at the article-tag write site.
 */
#[Alps('ArticleTags')]
#[Cacheable]
class ArticleTags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly ArticleTagCommandInterface $articleTagCmd,
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

    /**
     * Replace the article's tag link set with the given tag ids.
     *
     * This is the showcase's own write entry point for tag-relation changes.
     * `RefreshSameCommand` purges `app://self/cache/articletags?articleId={id}`
     * on completion, so the next GET re-renders against the updated relation.
     *
     * @param list<int> $tagIds
     */
    #[Alps('doUpdateCacheArticleTags')]
    #[JsonSchema(schema: 'write_response.json', params: 'cache_article_tags_update.json')]
    public function onPut(int $articleId, array $tagIds): static
    {
        $this->articleTagCmd->clear($articleId);
        foreach ($tagIds as $tagId) {
            $this->articleTagCmd->link($articleId, $tagId);
        }

        $this->code = Code::OK;
        $this->body = ['id' => $articleId];

        return $this;
    }
}
