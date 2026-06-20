---
layout: sample
title: "Cache showcase boundary"
order: 70
category: cache
status: by-design
source:
  - src/Resource/App/Cache/AuthorProfile.php
  - src/Resource/App/Cache/ArticleTags.php
test:
  - tests/Resource/App/Cache/AuthorProfileCacheTest.php
  - tests/Resource/App/Cache/ArticleTagsCacheTest.php
convention: docs/scope.md
upstream: [Web Cache, Resource-oriented design, Application as Documentation]
tags: [cache, showcase, by-design, boundary]
ai_use: "Use this to keep cache examples isolated while copying their dependency shapes."
ai_avoid: "Do not wire cache showcase resources into the main Article write path just to make the demo look production-complete."
---

## Cache showcase boundary

<section class="intent" markdown="1">

### Intent

Keep cache examples as an isolated showcase so readers can study dependency
shapes without mixing them into the principal CMS resource flow. The cache
patterns are reusable; the showcase resources themselves are teaching surfaces.

</section>

<section class="summary" markdown="1">

### Summary

Keep cache examples as teaching resources outside the main Article flow. Copy
the dependency shapes without wiring the demo resources into production paths.

</section>

<section class="useWhen" markdown="1">

### Use when

- You need a clear example of QueryRepository dependency behavior.
- You want to demonstrate one cache shape at a time.
- You need tests that pin zero-code and one-line cache invariants.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- You are implementing production invalidation for the main Article resource.
- You would couple a teaching resource to unrelated write paths.
- You are trying to remove intentional separation for apparent symmetry.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- // Main Article writes also purge the cache showcase.
- $this->cacheRepository->purge('app://self/cache/articletags?articleId=' . $id);
+ // Keep showcase invalidation self-contained.
+ // Copy the dependency shape into production code only when that production
+ // resource owns the dependency and its invalidation boundary.
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
#[Cacheable]
final class ArticleTags extends ResourceObject
{
    public function onGet(int $articleId): static
    {
        // Demonstrates body-derived dependency tags only inside the showcase.
    }

    public function onPut(int $articleId, array $tagIds): static
    {
        // Showcase-owned write entry point; RefreshSameCommand handles this URI.
    }
}
```

</section>

<section class="notes" markdown="1">

### Notes

- `by-design` means this separation is not a backlog item.
- The reusable idea is the cache dependency shape, not the exact URI surface.
- This protects the main Resource examples from becoming cache-demo plumbing.

</section>

### Links

- Source A: [`src/Resource/App/Cache/AuthorProfile.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Cache/AuthorProfile.php){: .goSource }
- Source B: [`src/Resource/App/Cache/ArticleTags.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Cache/ArticleTags.php){: .goSource }
- Test A: [`tests/Resource/App/Cache/AuthorProfileCacheTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/Cache/AuthorProfileCacheTest.php){: .goTest }
- Test B: [`tests/Resource/App/Cache/ArticleTagsCacheTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/Cache/ArticleTagsCacheTest.php){: .goTest }
- Convention: [`docs/scope.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/scope.md){: .goConvention }
