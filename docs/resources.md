# App resources

[日本語](ja/resources.md)

All App resources return HAL+JSON. Shapes below use the entity JSON Schema
under [../var/json_schema/](../var/json_schema) as the source of truth.
Page resources under `page://self/*` render Qiq HTML and are intentionally
separate from this App resource map.

## App entry point

There is intentionally no `app://self/` App resource. `Page/Index` is the
public HTML entry point, and HAL discoverability is demonstrated from
concrete top-level resources such as `article`, `articles`, `categories`,
and `tags`.

## `app://self/article`

### GET `{id}`

```json
{
  "id": 1,
  "slug": "getting-started-with-bear-sunday",
  "title": "…",
  "body": "…",
  "excerpt": "…",
  "status": "published",
  "publishedAt": "2026-01-01T10:07:00Z",
  "authorId": 1,
  "categoryId": 1,
  "_embedded": {
    "author": { "id": 1, "name": "…", "email": "…", "bio": "…" },
    "category": { "id": 1, "slug": "technology", "name": "…", … },
    "tagList": {
      "items": [ { "id": 4, "slug": "media-query", "name": "MediaQuery" } ],
      "count": 1
    }
  }
}
```

Response codes: `200`, `404`.

### POST

Body:
```json
{
  "slug": "string",
  "title": "string",
  "body": "string",
  "authorId": 1,
  "categoryId": 1,
  "status": "draft|published",
  "excerpt": "string|null",
  "publishedAt": "RFC3339 string|null",
  "tagIds": [1, 2]
}
```

`publishedAt` is accepted as RFC3339 input. The SQL backend stores it as a
database `DATETIME`, and read responses normalise it back to RFC3339 UTC.

`201` + `Location: /article?id={new_id}` + `{"id": N, "slug": "…"}`.

### PUT `{id}`
Body: `title`, `body`, `status`, optional `excerpt`, `publishedAt`,
optional `tagIds` (tri-state — omitted/`null`: leave existing tag links
untouched, `[]`: clear all tag links, non-empty list: replace the tag
set with the given ids). `200` / `404`.

### DELETE `{id}`
`204` / `404`.

## `app://self/article-publish`

POST. State-transition resource for moving one article from `draft` to
`published`.

Body:
```json
{"id": 1, "publishedAt": "RFC3339 string|null"}
```

`publishedAt` is optional. When omitted, the resource supplies the current
UTC timestamp before calling the command layer.

Response codes:

- `200` + `{"id": N, "slug": "...", "status": "published", "publishedAt": "..."}`
- `404` when the article does not exist
- `409` when the article is already published

## `app://self/articles`

GET. Query params: `page`, `perPage` (clamped 1..100, default 20),
`categoryId`, `tagId`, `authorId`, `status`. Omitting `status` at the App
layer returns all lifecycle states. Response:
```json
{"items": [...article summaries...], "page": 1, "perPage": 20, "count": 20, "totalCount": 50}
```

Reader-facing Page lists are stricter: `/articlelist` always restricts results
to published articles, while the admin list can show all, draft, or published
author-owned articles.

## `app://self/category` / `categories`

- GET `{id}` — single; GET — list.
- POST / PUT / DELETE — mirror the article shape.

## `app://self/tag` / `tags`

- GET `{id}` / list.
- POST / DELETE.

## `app://self/author`

- GET `{id}`.
- POST — `name`, `email`, optional `bio`.
- PUT `{id}`.

## `app://self/media`

- GET `{id}`.
- POST — `filename`, `mimeType`, `url`, optional `alt`, `width`, `height`.
- DELETE `{id}`.

## `app://self/media-upload`

- POST — `#[InputFile] FileUpload|ErrorFileUpload $file`, optional `alt`.
- Accepts `image/jpeg`, `image/png`, and `image/webp` up to 5 MiB.
- Stores files under `CMS_UPLOAD_DIR` (default `var/tmp/uploads`) and records the
  resulting Media metadata with the existing `MediaCommandInterface`.
- This is the canonical file-upload example; `app://self/media` remains the
  scalar metadata write path so readers can compare both parameter styles.

## Crawl/DataLoader companion resources (`app://self/crawl/*`)

These resources are a focused BEAR.Resource `linkCrawl` reference, not a
separate public API family. They model the author → articles → tags traversal
without manual resource fetching:

- `app://self/crawl/author?id=1` — crawl root.
- `app://self/crawl/articles?authorId=1` — article summary list.
- `app://self/crawl/tags?articleId=1` — tag list shape used for standalone
  reads and as the DataLoader row contract.

Run the graph through the resource client:

```php
$ro = $resource->crawl('app://self/crawl/author', 'author-tree', ['id' => 1]);
```

`Crawl\Author::onGet()` declares the `articleList` crawl link.
`Crawl\Articles::onGet()` declares the nested `tagList` crawl link with
`ArticleTagsDataLoader`, which batches all article ids into one
`TagQueryInterface::listByArticles()` call. The focused test
`tests/Resource/App/Crawl/CrawlDataLoaderTest.php` pins that behavior by
asserting one `tag_list_by_articles` query and zero per-article
`tag_list_by_article` queries.

## Cache showcase resources (`app://self/cache/*`)

A five-resource set under `src/Resource/App/Cache/*` that demonstrates the
BEAR QueryRepository cache patterns the codebase canonicalizes. The
showcase is hermetic — `composer demo:cache` runs it against an in-memory
`ArrayAdapter` bound by `CacheShowcaseModule`. See `docs/conventions.md`
§ 4 "Cache" for the canonical pattern, and the reflection tests under
`tests/Resource/App/Cache/` for the executable invariants.

### Pattern A — `#[Cacheable]`-only leaf (user-zero-code)

- `app://self/cache/author` (`Cache\Author`)
- `app://self/cache/tag` (`Cache\Tag`)

Both expose GET `{id}` and PUT `{id}`. Cache surface is one attribute:
`#[Cacheable]`. The framework writes the self URI tag, and
`RefreshSameCommand` purges it on write. No `Header::SURROGATE_KEY`, no
`UriTagInterface`, no `DonutRepositoryInterface` appears in the class.

### Pattern B — `#[Embed]`-only parent (automatic dependency, since `bear/query-repository` 1.16)

- `app://self/cache/authorprofile` (`Cache\AuthorProfile`) — GET only.
  Composes `Cache\Author` via `#[Embed(rel: 'author', src: 'app://self/cache/author')]`.
  Zero lines of cache code: `QueryRepository::setCacheDependency` walks
  `$ro->body` for `AbstractRequest` children before HAL rendering and
  auto-merges the child's URI tag into the parent's Surrogate-Key.
  No `UriTagInterface`, no `Header::SURROGATE_KEY` assignment in the
  class. The reflection test pins this contract. A missing `authorId`
  is rejected up-front with `Code::NOT_FOUND` (replacing `$this->body`
  drops the `#[Embed]` Request), and `CacheInterceptor` only stores
  responses with code 200 — anything else takes the purge branch, so a
  404 cannot be served from a stale cache entry.

### Pattern C — `fromAssoc` for body-derived variable-length dependencies

- `app://self/cache/articletags` (`Cache\ArticleTags`) — GET / PUT.
  Reads N tag rows from the DB and declares the variable-length
  dependency set in one line:
  `$this->headers[SURROGATE_KEY] = $uriTag->fromAssoc('app://self/cache/tag{?id}', $items)`.
  No `#[Embed]` — the dependency set comes from the body, so static
  embed declaration cannot express it. Mixing this with `#[Embed]` on
  the same resource is an anti-pattern: a pre-set Surrogate-Key
  short-circuits the auto-merge via `setCacheDependency`'s early return.
  PUT is the showcase's own write entry point for tag-relation
  changes; `RefreshSameCommand` purges the self URI tag, so the next
  GET re-queries and rebuilds the dependency set.

PUT on a child URI cascades through to the parent's ETag via the
Surrogate-Key tag. Run `composer demo:cache` to see the
GET → cached → child-PUT → invalidated → re-GET flow for both patterns.

### Pattern D — explicit `#[DonutCache]` preview

- `app://self/cache/articlepreview` (`Cache\ArticlePreview`) — GET only.
  This resource demonstrates the manual's explicit `#[DonutCache]` attribute
  without inventing a clock/random endpoint. It is scalar HAL output on purpose:
  donut-hole placeholders are string-renderer oriented, while HAL embeds are
  better demonstrated by Pattern B.

### Boundary — query-string variant purge policy

- `#[Purge(uri: 'app://self/articles')]` is the canonical URI example.
  Query-string variants such as `app://self/articles?categoryId=3` are separate
  cache entries and are not purged by that annotation.
- This project does not ship a custom article-list variant invalidator. Such a
  service can be valid in a production CMS, but it is application policy rather
  than a reusable BEAR.Sunday feature. Add it only when a real workflow depends
  on cached filter variants.

**Invalidation scope (intentional).** The showcase keeps its cache
surface self-contained: writes to the main `app://self/article`
resource that change `tagIds` are NOT wired into
`app://self/cache/articletags` invalidation. A production CMS that
needs cross-resource cascade would add an explicit
`DonutRepositoryInterface::purge()` (or equivalent tag invalidation)
at the article-tag write site — that coupling is deliberately kept
out of the showcase to preserve the canonical pattern.

## HAL links

Each Resource declares `#[Link]` attributes with URI templates (RFC 6570).
Example from `Article::onGet`:

```php
#[Link(rel: 'goArticleList', href: 'app://self/articles')]
#[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
#[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
```

Renderers for `hal-api-app` expand these into `_links` on the response.
Embedded resources (`_embedded.author`, `_embedded.category`,
`_embedded.tagList`, etc.) are populated manually inside `onGet` because
the IDs aren't known until the article row is fetched.
