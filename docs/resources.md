# App resources

[日本語](ja/resources.md)

All App resources return HAL+JSON. Shapes below use the entity JSON Schema
under [../var/json_schema/](../var/json_schema) as the source of truth.
Page resources under `page://self/*` render Qiq HTML and are intentionally
separate from this App resource map.

## `app://self/`

Entry point. Links to the main collections.

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

`201` + `Location: /article?id={new_id}` + `{"id": N, "slug": "…"}`.

### PUT `{id}`
Body: `title`, `body`, `status`, optional `excerpt`, `publishedAt`,
optional `tagIds` (tri-state — omitted/`null`: leave existing tag links
untouched, `[]`: clear all tag links, non-empty list: replace the tag
set with the given ids). `200` / `404`.

### DELETE `{id}`
`204` / `404`.

## `app://self/articles`

GET. Query params: `page`, `perPage` (clamped 1..100, default 20),
`categoryId`, `tagId`, `authorId`, `status`. Response:
```json
{"items": [...article summaries...], "page": 1, "perPage": 20, "count": 20, "totalCount": 50}
```

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

## Cache showcase resources (`app://self/cache/*`)

A four-resource set under `src/Resource/App/Cache/*` that demonstrates the
two BEAR QueryRepository cache patterns the codebase canonicalizes. The
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

### Pattern B — one-line `fromAssoc` parent (single-child or N-child)

- `app://self/cache/authorprofile` (`Cache\AuthorProfile`) — GET only.
  Composes `Cache\Author` via `#[Embed(rel: 'author', src: 'app://self/cache/author')]`
  so the HAL renderer materializes the child into `_embedded.author`.
  Cross-resource invalidation is one line:
  `$this->headers[SURROGATE_KEY] = $uriTag->fromAssoc('app://self/cache/author{?id}', [['id' => $authorId]])`.
- `app://self/cache/articletags` (`Cache\ArticleTags`) — GET only.
  Reads N tag rows from the DB and declares the same one line for the
  variable-length dependency set:
  `$this->headers[SURROGATE_KEY] = $uriTag->fromAssoc('app://self/cache/tag{?id}', $items)`.
  No `#[Embed]` — the dependency set comes from the body, so static
  embed declaration cannot express it.

PUT on a child URI cascades through to the parent's ETag via the
Surrogate-Key tag. Run `composer demo:cache` to see the
GET → cached → child-PUT → invalidated → re-GET flow for both patterns.

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
