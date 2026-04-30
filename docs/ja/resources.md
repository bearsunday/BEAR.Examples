# App リソース

[English](../resources.md)

すべての Resource は HAL+JSON を返します。以下の shape は
[../../var/schema/](../../var/schema) にある entity JSON Schema を source of truth
としています。

## `app://self/`

Entry point。主要な collection へのリンクを返します。

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
    "tags": [ { "id": 4, "slug": "media-query", "name": "MediaQuery" } ]
  }
}
```

Response codes: `200`、`404`。

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
  "publishedAt": "RFC3339 string|null"
}
```

`201` + `Location: /article?id={new_id}` + `{"id": N, "slug": "…"}`。

### PUT `{id}`
Body: `title`、`body`、`status`、optional `excerpt`、`publishedAt`、optional `tagIds`
(tri-state — 省略 / `null`: 既存の tag link を変更しない、`[]`: すべての tag link を
クリアする、non-empty list: 与えられた id 群で tag set を置き換える)。`200` / `404`。

### DELETE `{id}`
`204` / `404`。

## `app://self/articles`

GET。Query params: `page`、`perPage` (1..100 にクランプ、デフォルト 20)、
`categoryId`、`tagId`、`status`。Response:
```json
{"items": [...article summaries...], "page": 1, "perPage": 20, "count": 20}
```

## `app://self/category` / `categories`

- GET `{id}` — 単体取得; GET — 一覧取得。
- POST / PUT / DELETE — article の shape をミラーします。

## `app://self/tag` / `tags`

- GET `{id}` / list。
- POST / DELETE。

## `app://self/author`

- GET `{id}`。
- POST — `name`、`email`、optional `bio`。
- PUT `{id}`。

## `app://self/media`

- GET `{id}`。
- POST — `filename`、`mimeType`、`url`、optional `alt`、`width`、`height`。
- DELETE `{id}`。

## HAL links

各 Resource は URI template (RFC 6570) を持つ `#[Link]` attribute を宣言します。
`Article::onGet` の例:

```php
#[Link(rel: 'articles', href: 'app://self/articles')]
#[Link(rel: 'author', href: 'app://self/author{?id}')]
#[Link(rel: 'category', href: 'app://self/category{?id}')]
```

`hal-api-app` の renderer はこれらをレスポンスの `_links` に展開します。
embedded resource (`_embedded.author` 等) は、article 行を fetch するまで id が
分からないため、`onGet` 内で手動で埋めます。
