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
