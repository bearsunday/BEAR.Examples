# Code Reading Guide

[日本語](../ja/reading-guide.md)

This guide is for reading the repository as code, not for learning every API
surface. Article GET is the first tour because it is small and touches the
main layers; the later passes cover writes, collections, contexts, and fakes.

## Pass 1: Canonical Read Flow

Read Article GET first. It touches the main architectural pieces without the
extra write-path concerns.

1. `src/Resource/App/Article.php` — Resource boundary, HAL links/embeds, and
   response body shape.
2. `src/Entity/Article.php` — domain data and presentation helpers that should
   not be scattered across arrays.
3. `src/Query/ArticleQueryInterface.php` — declarative read contract via
   `#[DbQuery]`.
4. `var/db/sql/article_item.sql` — SQL shape expected by the query interface.
5. `tests/Resource/App/ArticleTest.php` — behavior contract from the client
   side.
6. `tests/Fake/FakeSqlQuery.php` — fake dispatch rules; this explains the
   query contract more concretely than the interface alone.

## Pass 2: Write Flow

Read Article writes next. This shows how input validation, commands, and
response reconstruction fit together.

1. `src/Resource/App/Article.php::onPost()` and `onPut()` — Resource-level
   input DTOs, status codes, and response body construction.
2. `src/Input/ArticleCreateInput.php` and `ArticleUpdateInput.php` — boundary
   normalization before the Resource calls command methods.
3. `src/Query/ArticleCommandInterface.php` and
   `src/Query/ArticleTagCommandInterface.php` — write contracts.
4. `var/db/sql/article_add.sql`, `article_update.sql`, and
   `article_tag_*.sql` — SQL-side effects and parameter names.
5. `tests/Resource/App/ArticleTest.php` — create/update/delete behavior.

## Pass 3: Resource Families

After Article, read the smaller families to see the same conventions without
the full Article surface:

- `Author.php`, `Category.php`, and `Tag.php` for simple item resources.
- `Articles.php`, `Categories.php`, and `Tags.php` for collection resources.
- `Media.php` for upload-like data and filename-based lookup.
- `MediaUpload.php` for the `#[InputFile]` upload boundary.
- `Auth.php` for an action-style resource that is not CRUD-shaped.
- `src/Resource/App/Crawl/*` plus `ArticleTagsDataLoader` for
  `linkCrawl()` and DataLoader batching without manual resource fetching.

## Pass 4: Runtime Contexts

Then read composition and test support:

- `src/Module/AppModule.php`, `FakeModule.php`, and `TestModule.php` for
  context-specific bindings.
- `src/Module/ProdModule.php` for the production overlay and optional Redis
  cache storage.
- `tests/Fake/FakeSqlQuery.php` for in-memory MediaQuery behavior.
- `tests/Fake/FakeExtendedPdoProvider.php` for the raw-PDO variation tests.
- `bin/demo.php` and `bin/demo-variations.php` for runnable examples.
- `tests/Resource/App/Crawl/CrawlDataLoaderTest.php` for the focused
  crawl/DataLoader query-count contract.

## What To Notice By Layer

| Area | Files | Reading focus |
|---|---|---|
| Resource layer | `src/Resource/App/*` | HTTP method shape, status codes, body construction, `#[JsonSchema]`, `#[Link]`, and `#[Embed]`. |
| Entity layer | `src/Entity/*` | Immutable domain data, computed fields, and behavior that should stay near the data. |
| Query layer | `src/Query/*` | Read/write split, method names, and how attributes map PHP methods to SQL files. |
| SQL layer | `var/db/sql/*` | Column aliases, parameter names, and row shapes consumed by entities/resources. |
| Composition | `src/Module/*` | Context-specific wiring: production, fake, and test. |
| Tests and fakes | `tests/*` | The executable contract. Fakes should preserve semantics, not merely return convenient data. |

## Comparison Guides

Use the focused comparison docs when reading tradeoffs:

- [`src/Resource/App/Variations/README.md`](../../src/Resource/App/Variations/README.md)
  for the Article GET variation set.
- [`src/Resource/App/Variations/README.ja.md`](../../src/Resource/App/Variations/README.ja.md)
  for the Japanese version.
- [`docs/conventions.md`](../conventions.md) when you need the rule behind a
  naming or shape choice.

## Reading Rule

Treat [`docs/conventions.md`](../conventions.md) as the current rulebook.
Treat `docs/journal/*` as historical context: useful for understanding why a
decision exists, but not the source of truth for new code.
