# `crawl-data-loader`

**`#[Link(crawl:...)]` + DataLoaderでN+1を解消する** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `showcase`
- **Aliases:** crawl, linkCrawl, DataLoader, DataLoaderInterface, N+1, batch query, resource graph, クロール, リソースグラフ, バッチクエリ, N+1解消
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_link.html
- **Use when:** `#[Link(crawl:...)]`でリソースグラフを構築し、子リソースのN+1クエリをバッチで解消したい。

## 例

### Resource（親 — crawl名の宣言）

`crawl:` でグラフ名を宣言し、`href` のURI templateで子を指す。404分岐や body 詰めは [`db-read-one-entity`](./db-read-one-entity.md) と同じ:

```php
#[Link(crawl: 'author-tree', rel: 'articleList', href: 'app://self/crawl/articles?authorId={id}')]
public function onGet(int $id): static
```

### Resource（子 — dataLoader指定）

N+1が起きる子リンクに `dataLoader:` を指定する。指定が無ければ子リソースが1件ずつ呼ばれる:

```php
#[Link(
    crawl: 'author-tree',
    rel: 'tagList',
    href: 'app://self/crawl/tags?articleId={id}',
    dataLoader: ArticleTagsDataLoader::class,
)]
public function onGet(int $authorId, int $perPage = 100): static
```

### DataLoader

`__invoke(array $queries): array` にURI templateから展開されたクエリの束が渡る。keyを集めてバッチQueryに委譲する:

```php
final readonly class ArticleTagsDataLoader implements DataLoaderInterface
{
    public function __construct(
        private TagQueryInterface $tag,
    ) {
    }

    public function __invoke(array $queries): array
    {
        $articleIds = [];
        foreach ($queries as $query) {
            if (! isset($query['articleId'])) {
                continue;
            }

            $articleIds[(int) $query['articleId']] = (int) $query['articleId'];
        }

        if ($articleIds === []) {
            return [];
        }

        return $this->tag->listByArticles(array_values($articleIds));
    }
}
```

### QueryInterface

バッチ読みは `list<Variant>` の複数形。返す行に分配key（`articleId`）を含める:

```php
/**
 * @param list<int> $articleIds
 *
 * @return list<array{articleId: int, id: int, slug: string, name: string}>
 */
#[DbQuery('tag_list_by_articles')]
public function listByArticles(array $articleIds): array;
```

### Client

`ResourceInterface::crawl($uri, $crawlName, $query)` でグラフごと取得する:

```php
$ro = $this->resource->crawl('app://self/crawl/author', 'author-tree', ['id' => 1]);
```

## Naming

per-item読みとバッチ読みは `list<Variant>` の単複で対にする:

| 形 | メソッド | SQL ファイル |
|---|---|---|
| per-item 読み | `listByArticle(int $articleId)` | `tag_list_by_article.sql` |
| バッチ読み | `listByArticles(array $articleIds)` | `tag_list_by_articles.sql` |

DataLoader class は `<親><子コレクション>DataLoader`（`ArticleTagsDataLoader`）とし、`src/DataLoader/` に置く。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[Link(crawl: ...)]` でcrawl名を指定し、リソースグラフを宣言的に構築すると決めたか。
- [ ] N+1が起きる子リソースに `dataLoader: DataLoaderClass::class` を指定し、`DataLoaderInterface::__invoke()` でバッチクエリを実装すると決めたか。
- [ ] DataLoaderはBeta（`bear/resource:1.x-dev`）である前提を確認したか。

## Source

- [`src/Resource/App/Crawl/Author.php`](../src/Resource/App/Crawl/Author.php)
- [`src/Resource/App/Crawl/Articles.php`](../src/Resource/App/Crawl/Articles.php)
- [`src/Resource/App/Crawl/Tags.php`](../src/Resource/App/Crawl/Tags.php)
- [`src/DataLoader/ArticleTagsDataLoader.php`](../src/DataLoader/ArticleTagsDataLoader.php)
- [`src/Query/TagQueryInterface.php::listByArticles()`](../src/Query/TagQueryInterface.php)

## Tests

- [`tests/Resource/App/Crawl/CrawlDataLoaderTest.php`](../tests/Resource/App/Crawl/CrawlDataLoaderTest.php)

## Key points

`#[Link(crawl: ...)]` でグラフ名を宣言。`DataLoaderInterface::__invoke(array $queries): array` でバッチクエリ。keyはURI templateから自動推論。clientからは `ResourceInterface::crawl($uri, $crawlName, $query)` またはfluent DSLの `linkCrawl($rel)` で実行する。

## Do not

- DataLoaderが返す行からkey column（この例では `articleId`）を落とさない — 分配keyが無い行は例外になる。

## マスター確認（After）

- [ ] batch SQL（`tag_list_by_articles` 相当）が1回、per-item SQLが0回になることをquery logで `CrawlDataLoaderTest.php` 相当で green。

## See also

- [`hal-embed`](./hal-embed.md) — `#[Embed]` が埋めるのは結果でなくrequest。crawlグラフを可能にする区別
- [`hal-link`](./hal-link.md) — `#[Link]` でHALリンクを宣言する基本形
- [`async-embed-parallel`](./async-embed-parallel.md) — 同じrequest埋め込みを並列実行で解く形
- [`db-read-list-pager`](./db-read-list-pager.md) — 子Resourceの一覧取得（Pager）
- [`db-read-one-entity`](./db-read-one-entity.md) — 親Resourceの1件取得の型
- [`fake-sql-query`](./fake-sql-query.md) — query logでSQL発行回数をpinする土台
