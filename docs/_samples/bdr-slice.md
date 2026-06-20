---
layout: sample
title: "BDR slice: Bound / Domain / Resource"
order: 0
category: architecture
status: canonical
source:
  - src/Resource/App/Article.php
  - src/Entity/Article.php
  - src/Query/ArticleQueryInterface.php
  - src/Query/ArticleCommandInterface.php
  - var/db/sql/article_item.sql
  - var/db/sql/article_add.sql
test:
  - tests/Resource/App/ArticleTest.php
  - tests/Smoke/MediaQuerySmokeTest.php
convention: docs/architecture.md
upstream: [BDR, Ray.MediaQuery, Resource-oriented design, Application as Documentation]
tags: [bdr, architecture, resource, entity, media-query, sql]
ai_use: "Start here when deciding where a new behavior belongs: Bound Resource, Domain Entity, Query/Command interface, or SQL file."
ai_avoid: "Do not add service or repository layers that bypass the BDR slice unless a convention explicitly calls for one."
---

## BDR slice: Bound / Domain / Resource

**Language:** [Japanese]({{ '/ja/catalog/bdr-slice/' | relative_url }})

<section class="intent" markdown="1">

### Intent

Show the canonical vertical slice of this reference CMS. A URI starts at a
Bound resource, moves through Query/Command interfaces, hydrates Domain entities,
and ends in SQL files. The important rule is not the acronym itself; it is that
each layer has a small, named responsibility and source/test evidence.

</section>

<section class="summary" markdown="1">

### Summary

This is the entry card for the catalog: keep URI behavior in Bound resources,
invariant data in Domain entities, and database access in Query/Command interfaces
plus SQL files.

</section>

<section class="useWhen" markdown="1">

### Use when

- You are adding a new URI or extending an existing App resource.
- You need to decide whether code belongs in Resource, Entity, Query/Command, or SQL.
- You want an AI agent to follow the project shape before copying smaller patterns.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- You only need a small variation inside an existing Resource method; use the narrower card.
- You are documenting a comparison-only implementation under `src/Resource/App/Variations/`.
- You are trying to hide a cross-layer concern in an untested service because it feels familiar.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- Controller
-   -> Repository
-   -> array row
-   -> ad-hoc response
+ Bound:    src/Resource/App/Article.php
+ Domain:   src/Entity/Article.php
+ Resource: src/Query/ArticleQueryInterface.php
+ Resource: src/Query/ArticleCommandInterface.php
+ SQL:      var/db/sql/article_item.sql
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
// Bound: URI and HTTP method boundary.
final class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleCommandInterface $articleCmd,
    ) {}

    public function onGet(int $id): static
    {
        $article = $this->article->item($id);
        // map Domain entity to Resource body
        return $this;
    }
}

// Domain: invariant data shape.
final readonly class Article
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
    ) {}
}

// Resource: MediaQuery interface backed by SQL id.
interface ArticleQueryInterface
{
    #[DbQuery('article_item')]
    public function item(int $id): Article|null;
}
```

</section>

<section class="notes" markdown="1">

### Notes

- `Bound` is where BEAR.Resource attributes, validation, links, embeds, and status codes live.
- `Domain` entities are final readonly shapes hydrated from SELECT column order.
- `Resource` in BDR is the MediaQuery boundary: read and write interfaces in `src/Query/`.
- SQL files are part of the slice; method names and SQL ids must line up.
- Tests should exercise the same Resource code through fake or real infrastructure, not through per-test mocks.

</section>

### Links

- Source: [`src/Resource/App/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Article.php){: .goSource }
- Entity: [`src/Entity/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Entity/Article.php){: .goSource }
- Query: [`src/Query/ArticleQueryInterface.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Query/ArticleQueryInterface.php){: .goSource }
- Command: [`src/Query/ArticleCommandInterface.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Query/ArticleCommandInterface.php){: .goSource }
- SQL: [`var/db/sql/article_item.sql`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/var/db/sql/article_item.sql){: .goSource }
- Test: [`tests/Resource/App/ArticleTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/ArticleTest.php){: .goTest }
- Smoke: [`tests/Smoke/MediaQuerySmokeTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Smoke/MediaQuerySmokeTest.php){: .goTest }
- Convention: [`docs/architecture.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/architecture.md#bdr-pattern-bound--domain--resource){: .goConvention }
