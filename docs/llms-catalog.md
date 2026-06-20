---
title: LLM Catalog Index
permalink: /llms-catalog/
---

# LLM Catalog Index

A compact index for AI agents. Each sample is a reusable pattern card. The
`Pattern diff` sections in cards are conceptual rewrite shapes, not historical
git diffs.


## Status and snippet contract

- `canonical`: copy this pattern when the same conditions apply.
- `showcase`: copy the shape, but keep the demo boundary unless the card says otherwise.
- Pattern diffs are conceptual rewrite shapes, not historical git diffs.
- Shape excerpts show implementation shape; use Source and Test links for complete code.

| Sample | Status | Use | Avoid | Source | Test |
|---|---|---|---|---|---|
| [BDR slice: Bound / Domain / Resource]({{ '/samples/bdr-slice/' | relative_url }}) | canonical | Start here when deciding where a new behavior belongs: Bound Resource, Domain Entity, Query/Command interface, or SQL file. | Do not add service or repository layers that bypass the BDR slice unless a convention explicitly calls for one. | `src/Resource/App/Article.php`, `src/Entity/Article.php`, `src/Query/ArticleQueryInterface.php`, `src/Query/ArticleCommandInterface.php`, `var/db/sql/article_item.sql` | `tests/Resource/App/ArticleTest.php`, `tests/Smoke/MediaQuerySmokeTest.php` |
| [Input DTO via `#[Input]`]({{ '/samples/input-dto/' | relative_url }}) | canonical | Use when a Resource method needs a coherent typed request shape. | Do not convert scalar contrast examples just for symmetry. | `src/Resource/App/Article.php`, `src/Input/ArticleCreateInput.php` | `tests/Resource/App/ArticleTest.php` |
| [Preserve `#[Embed]` slots with body union]({{ '/samples/embed-body-union/' | relative_url }}) | canonical | Use when an `onGet` method has `#[Embed]` requests that need query parameters discovered after fetching the entity. | Do not assign `$this->body = [...]` after embed slots exist. | `src/Resource/App/Article.php` | `tests/Hypermedia/HalEnvelopeContractTest.php` |
| [FakeSqlQuery as real in-memory infrastructure]({{ '/samples/fake-sql-query/' | relative_url }}) | canonical | Use when the whole Resource stack should run without a database while preserving `#[DbQuery]` dispatch semantics. | Do not replace Resource tests with mocks that bypass DI or MediaQuery dispatch. | `tests/Fake/FakeSqlQuery.php`, `src/Module/FakeModule.php` | `tests/Smoke/MediaQuerySmokeTest.php` |
| [Hypermedia workflow test]({{ '/samples/hypermedia-workflow-test/' | relative_url }}) | canonical | Use when validating that resources are connected by HAL rels as an ALPS user story. | Do not compress a story into one method with hard-coded mid-chain URIs. | `tests/Hypermedia/EditorManagesArticleTest.php`, `tests/Hypermedia/ReaderBrowsesByTagTest.php`, `tests/Hypermedia/AbstractWorkflowTestCase.php` | `tests/Hypermedia/EditorManagesArticleTest.php`, `tests/Hypermedia/ReaderBrowsesByTagTest.php` |
| [Cache showcase boundary]({{ '/samples/cache-showcase-boundary/' | relative_url }}) | by-design | Use this to keep cache examples isolated while copying their dependency shapes. | Do not wire cache showcase resources into the main Article write path just to make the demo look production-complete. | `src/Resource/App/Cache/AuthorProfile.php`, `src/Resource/App/Cache/ArticleTags.php` | `tests/Resource/App/Cache/AuthorProfileCacheTest.php`, `tests/Resource/App/Cache/ArticleTagsCacheTest.php` |
| [Location header as hypermedia transition]({{ '/samples/location-transition/' | relative_url }}) | historical | Use this when a POST creates a resource and the client must discover the new URI from Location. | Do not treat Location-following as a direct coupling smell; it is the hypermedia transition for unsafe creation. | `src/Resource/App/Article.php`, `tests/Hypermedia/EditorManagesArticleTest.php` | `tests/Hypermedia/EditorManagesArticleTest.php` |
| [QueryRepository cache dependency shapes]({{ '/samples/cache-dependency-shapes/' | relative_url }}) | showcase | Use when showing the two allowed cross-resource cache dependency shapes inside the isolated cache showcase. | Do not mix `#[Embed]` auto dependency and manual `fromAssoc()` on the same response. | `src/Resource/App/Cache/AuthorProfile.php`, `src/Resource/App/Cache/ArticleTags.php` | `tests/Resource/App/Cache/AuthorProfileCacheTest.php`, `tests/Resource/App/Cache/ArticleTagsCacheTest.php` |
