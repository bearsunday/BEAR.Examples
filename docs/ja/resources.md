# App リソース

[English](../resources.md)

すべての App Resource は HAL+JSON を返します。以下の shape は
[../../var/json_schema/](../../var/json_schema) にある entity JSON Schema を
source of truth としています。`page://self/*` の Page Resource は Qiq HTML を
render し、この App resource map とは意図的に分けています。

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
    "tagList": {
      "items": [ { "id": 4, "slug": "media-query", "name": "MediaQuery" } ],
      "count": 1
    }
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
  "publishedAt": "RFC3339 string|null",
  "tagIds": [1, 2]
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
`categoryId`、`tagId`、`authorId`、`status`。Response:
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
#[Link(rel: 'goArticleList', href: 'app://self/articles')]
#[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
#[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
```

`hal-api-app` の renderer はこれらをレスポンスの `_links` に展開します。
embedded resource (`_embedded.author`、`_embedded.category`、
`_embedded.tagList` 等) は、article 行を fetch するまで id が分からないため、
`onGet` 内で手動で埋めます。
