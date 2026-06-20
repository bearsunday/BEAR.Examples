---
layout: sample
title: "QueryRepository cache dependency shapes"
order: 50
category: cache
status: showcase
source:
  - src/Resource/App/Cache/AuthorProfile.php
  - src/Resource/App/Cache/ArticleTags.php
test:
  - tests/Resource/App/Cache/AuthorProfileCacheTest.php
  - tests/Resource/App/Cache/ArticleTagsCacheTest.php
convention: docs/conventions.md
upstream: [Web Cache, Cache invalidation by semantic methods and dependencies, ETag, Donut caching]
tags: [cache, query-repository, embed, surrogate-key]
ai_use: "Use this when showing the two allowed cross-resource cache dependency shapes inside the isolated cache showcase."
ai_avoid: "Do not mix #[Embed] auto dependency and manual fromAssoc() on the same response."
---

## QueryRepository cache dependency shapes

<section class="intent" markdown="1">

### Intent

Show the two allowed cross-resource cache dependency shapes in this isolated
showcase: `#[Embed]`-only auto dependency for statically declared children, and
exactly one `UriTagInterface::fromAssoc()` assignment for body-derived dynamic
child sets. The shapes are reusable; the showcase resource boundary is kept
separate from the main Article path on purpose.

</section>

<section class="summary" markdown="1">

### Summary

Use `#[Embed]` for static cache dependencies and exactly one `fromAssoc()`
assignment for dynamic dependency sets. The shape is reusable, but the showcase
boundary stays separate from the main Article path.

</section>

<section class="useWhen" markdown="1">

### Use when

- A cacheable parent depends on another resource's URI.
- The dependency is either statically expressible by `#[Embed]` or dynamically derived from rows.
- You want tests to pin that no extra manual cache primitives were introduced.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- The resource is a simple leaf; `#[Cacheable]` alone is enough.
- You would mix `#[Embed]` auto dependency and manual `Header::SURROGATE_KEY` on the same response.
- You are trying to wire the cache showcase into main `Article` writes; that coupling is intentionally out of scope.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- $this->headers[Header::SURROGATE_KEY] = 'app://self/cache/author?id=' . $authorId;
+ #[Embed(rel: 'author', src: 'app://self/cache/author')]
+ $this->body['author']->addQuery(['id' => $authorId]);

- foreach ($items as $item) {
-     $this->headers[Header::SURROGATE_KEY] .= ' ' . $item['id'];
- }
+ if ($items !== []) {
+     $this->headers[Header::SURROGATE_KEY]
+         = $this->uriTag->fromAssoc('app://self/cache/tag{?id}', $items);
+ }
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
use BEAR\QueryRepository\Header;
use BEAR\QueryRepository\UriTagInterface;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\Embed;

#[Cacheable]
final class AuthorProfile extends ResourceObject
{
    #[Embed(rel: 'author', src: 'app://self/cache/author')]
    public function onGet(int $authorId): static
    {
        $this->body['author']->addQuery(['id' => $authorId]);
        $this->body += ['authorId' => $authorId];

        return $this;
    }
}

#[Cacheable]
final class ArticleTags extends ResourceObject
{
    public function __construct(private readonly UriTagInterface $uriTag) {}

    public function onGet(int $articleId): static
    {
        $items = $this->tag->listByArticle($articleId);
        if ($items !== []) {
            $this->headers[Header::SURROGATE_KEY]
                = $this->uriTag->fromAssoc('app://self/cache/tag{?id}', $items);
        }

        return $this;
    }
}
```

</section>

<section class="notes" markdown="1">

### Notes

- `showcase` means the cache resources are intentionally isolated demonstration surfaces.
- Pick `#[Embed]` when the dependency set is statically expressible.
- Pick `fromAssoc()` only when the dependency set is body-derived and variable length.
- Never write the self URI into `Header::SURROGATE_KEY`; the framework owns that.
- Tests use reflection to prevent manual cache code from creeping into the zero-code examples.

</section>

### Links

- Source A: [`src/Resource/App/Cache/AuthorProfile.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Cache/AuthorProfile.php){: .goSource }
- Source B: [`src/Resource/App/Cache/ArticleTags.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Cache/ArticleTags.php){: .goSource }
- Test A: [`tests/Resource/App/Cache/AuthorProfileCacheTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/Cache/AuthorProfileCacheTest.php){: .goTest }
- Test B: [`tests/Resource/App/Cache/ArticleTagsCacheTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/Cache/ArticleTagsCacheTest.php){: .goTest }
- Convention: [`docs/conventions.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/conventions.md){: .goConvention }
