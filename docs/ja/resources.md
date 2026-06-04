# App リソース

[English](../resources.md)

すべての App Resource は HAL+JSON を返します。以下の shape は
[../../var/json_schema/](../../var/json_schema) にある entity JSON Schema を
source of truth としています。`page://self/*` の Page Resource は Qiq HTML を
render し、この App resource map とは意図的に分けています。

## App entry point

`app://self/` の App resource は意図的にありません。public HTML entry は
`Page/Index` です。HAL discoverability は `article`、`articles`、
`categories`、`tags` など具体的な top-level resource から示します。

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

`publishedAt` は RFC3339 入力として受け付けます。SQL backend では database
`DATETIME` として保存し、read response では RFC3339 UTC に正規化して返します。

`201` + `Location: /article?id={new_id}` + `{"id": N, "slug": "…"}`。

### PUT `{id}`
Body: `title`、`body`、`status`、optional `excerpt`、`publishedAt`、optional `tagIds`
(tri-state — 省略 / `null`: 既存の tag link を変更しない、`[]`: すべての tag link を
クリアする、non-empty list: 与えられた id 群で tag set を置き換える)。`200` / `404`。

### DELETE `{id}`
`204` / `404`。

## `app://self/article-publish`

POST。1 件の article を `draft` から `published` へ移す state-transition
resource です。

Body:
```json
{"id": 1, "publishedAt": "RFC3339 string|null"}
```

`publishedAt` は任意です。省略時は resource が現在の UTC timestamp を補ってから
command layer を呼びます。

Response codes:

- `200` + `{"id": N, "slug": "...", "status": "published", "publishedAt": "..."}`
- article が存在しないとき `404`
- すでに published のとき `409`

## `app://self/articles`

GET。Query params: `page`、`perPage` (1..100 にクランプ、デフォルト 20)、
`categoryId`、`tagId`、`authorId`、`status`。App layer では `status` を省略すると
すべての lifecycle state を返します。Response:
```json
{"items": [...article summaries...], "page": 1, "perPage": 20, "count": 20, "totalCount": 50}
```

Reader-facing Page list の `/articlelist` はより厳しく、常に published article に
制限します。Admin list は author-owned article の all / draft / published を表示できます。

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

## `app://self/media-upload`

- POST — `#[InputFile] FileUpload|ErrorFileUpload $file`、optional `alt`。
- `image/jpeg`、`image/png`、`image/webp` を 5 MiB まで受け付けます。
- ファイル本体は `CMS_UPLOAD_DIR` (default `var/tmp/uploads`) に保存し、既存の
  `MediaCommandInterface` で metadata を登録します。
- `app://self/media` は scalar metadata write の対比例として残します。

## Crawl/DataLoader companion resources (`app://self/crawl/*`)

これらは通常の public API family ではなく、BEAR.Resource の `linkCrawl` を読むための
focused companion です。author → articles → tags の traversal を、resource class
内の手動 fetch ではなく crawl graph と DataLoader で表現します。

- `app://self/crawl/author?id=1` — crawl root。
- `app://self/crawl/articles?authorId=1` — article summary list。
- `app://self/crawl/tags?articleId=1` — standalone read と DataLoader row contract
  の両方で使う tag list shape。

実行は resource client の `crawl()` で行います。

```php
$ro = $resource->crawl('app://self/crawl/author', 'author-tree', ['id' => 1]);
```

`Crawl\Author::onGet()` が `articleList` crawl link を宣言し、
`Crawl\Articles::onGet()` が nested `tagList` crawl link と
`ArticleTagsDataLoader` を宣言します。DataLoader は article id 群をまとめて
`TagQueryInterface::listByArticles()` へ渡します。
`tests/Resource/App/Crawl/CrawlDataLoaderTest.php` は
`tag_list_by_articles` が 1 回だけ呼ばれ、article ごとの `tag_list_by_article` が
呼ばれないことを固定しています。

## Cache showcase resources (`app://self/cache/*`)

`src/Resource/App/Cache/*` 配下の 5 resource は、QueryRepository cache の
canonical pattern を示すための hermetic な showcase です。`composer demo:cache`
は `CacheShowcaseModule` が bind する in-memory `ArrayAdapter` 上で動きます。
詳しい規約は `docs/conventions.md` §4 "Cache" と
`tests/Resource/App/Cache/` の reflection tests を参照してください。

### Pattern A — `#[Cacheable]` だけの leaf

- `app://self/cache/author`
- `app://self/cache/tag`

どちらも GET `{id}` / PUT `{id}` を持ちます。Resource 側の cache surface は
`#[Cacheable]` だけです。`Header::SURROGATE_KEY`、`UriTagInterface`、
`DonutRepositoryInterface` を class に持ち込みません。

### Pattern B — `#[Embed]` だけの parent

- `app://self/cache/authorprofile`

`Cache\Author` を `#[Embed(rel: 'author', src: 'app://self/cache/author')]` で
合成します。`bear/query-repository` 1.16 以降では、HAL rendering 前に
`QueryRepository::setCacheDependency` が child の URI tag を parent の
Surrogate-Key に自動 merge します。Resource class に手書き cache code はありません。

### Pattern C — body-derived dependency の `fromAssoc`

- `app://self/cache/articletags`

DB から読んだ N 件の tag row から variable-length な dependency set を宣言します:
`$this->headers[SURROGATE_KEY] = $uriTag->fromAssoc('app://self/cache/tag{?id}', $items)`。
body から決まる依存なので、static な `#[Embed]` では表現しません。

### Pattern D — 明示的な `#[DonutCache]`

- `app://self/cache/articlepreview`

公式マニュアルの `#[DonutCache]` attribute を、CMS の article preview として示します。
HAL API では scalar-only にしています。donut-hole placeholder は string renderer 向けで、
HAL embed の依存表現は Pattern B の `#[Embed]` + `#[Cacheable]` で示します。

### Boundary — query-string variant purge policy

- `#[Purge(uri: 'app://self/articles')]` は canonical URI の例です。
  `app://self/articles?categoryId=3` のような query-string variant は別 cache
  entry で、この annotation だけでは purge されません。
- この project では article list variant 用の custom invalidator は持ちません。
  production CMS では妥当な service になり得ますが、BEAR.Sunday の再利用可能な
  feature というより application policy なので、実 workflow が必要とする時だけ追加します。

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
