# `db-read-list-pager`

**DBから一覧をページングして読む** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `canonical`
- **Aliases:** list query, collection resource, pager, `PagesInterface`, `#[Pager]`, article list, filtering, ページング, ページネーション, 一覧取得, 絞り込み, ページャ
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** collection resourceで一覧、絞り込み、ページングを扱いたい。

## 例

### QueryInterface

`#[Pager]` を付けた method は `PagesInterface` を返す。`perPage:` は引数名string（この例）とint固定値の両対応:

```php
#[DbQuery('article_list')]
#[Pager(perPage: 'perPage')]
public function list(
    int|null $categoryId = null,
    int|null $tagId = null,
    int|null $authorId = null,
    string|null $status = null,
    int $perPage = 20,
): PagesInterface;
```

### Resource

`page` / `perPage` の範囲外入力はResource側でclampする。`count($pages)` でCOUNT SQL、`$pages[$page]` でそのページのSQLが遅延実行される:

```php
public function onGet(
    int $page = 1,
    int $perPage = 20,
    int|null $categoryId = null,
    int|null $tagId = null,
    int|null $authorId = null,
    string|null $status = null,
): static {
    $page = $page < 1 ? 1 : $page;
    $perPage = $perPage < 1 ? 20 : ($perPage > 100 ? 100 : $perPage);
    $pages = $this->article->list(
        categoryId: $categoryId,
        tagId: $tagId,
        authorId: $authorId,
        status: $status,
        perPage: $perPage,
    );
    $totalPages = max(1, (int) ceil(count($pages) / $perPage));
    $page = min($page, $totalPages);
    $articlePage = $pages[$page];
    assert($articlePage instanceof Page);
    /** @var list<array<string, mixed>> $rows */
    $rows = $articlePage->data;
    $items = $this->articleFactory->fromRows($rows);

    $this->body = [
        'items' => array_map(static fn ($a) => [
            'id' => $a->id,
            'slug' => $a->slug,
            'title' => $a->title,
            'excerpt' => $a->excerpt,
            'status' => $a->status->value,
            'publishedAt' => $a->publishedAt,
            'authorId' => $a->authorId,
            'categoryId' => $a->categoryId,
        ], $items),
        'page' => $articlePage->current,
        'perPage' => $articlePage->maxPerPage,
        'count' => count($items),
        'totalCount' => $articlePage->total,
    ];

    return $this;
}
```

Page側の一覧（`ArticleList::onGet()`）も同じ `list()` を叩き、`$articlePage->hasNext` をtemplateへ渡す — [`page-resource-list`](./page-resource-list.md) を参照。

### Factory（rows → Entity）

pager経路の `$pages[$page]->data` はsnake_caseキーの連想配列で返るため、Resource側でEntityへ変換する:

```php
public function fromRows(array $rows): array
{
    return array_map(fn (array $row): Article => $this->fromRow($row), $rows);
}

public function fromRow(array $row): Article
{
    return $this->factory(
        (int) $row['id'],
        (string) $row['slug'],
        (string) $row['title'],
        (string) $row['body'],
        isset($row['excerpt']) ? (string) $row['excerpt'] : null,
        (string) $row['status'],
        $row['published_at'],
        (int) $row['author_id'],
        (int) $row['category_id'],
    );
}
```

### SQL

filterは `(:param IS NULL OR ...)` 形で省略可能にする。`LIMIT` / `OFFSET` と COUNT はpagerが付与するのでSQLには書かない:

```sql
SELECT
    a.id,
    a.slug,
    a.title,
    a.body,
    a.excerpt,
    a.status,
    a.published_at,
    a.author_id,
    a.category_id
FROM articles a
WHERE (:categoryId IS NULL OR a.category_id = :categoryId)
  AND (:authorId IS NULL OR a.author_id = :authorId)
  AND (:status IS NULL OR a.status = :status)
  AND (
    :tagId IS NULL
    OR EXISTS (SELECT 1 FROM article_tags at WHERE at.article_id = a.id AND at.tag_id = :tagId)
  )
ORDER BY a.published_at DESC, a.id DESC
```

## Naming

| 形 | メソッド | SQL ファイル |
|---|---|---|
| 一覧 | `list()` / `list<Variant>()` | `<entity>_list.sql` / `<entity>_list_by_<x>.sql` |

`item` ↔ `list` は BEAR の resource 形と対応する語彙のペア — `Article`（item resource）↔ `Articles`（collection resource）。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] item resource（1件）と collection resource（一覧）を別Resourceに分けると決めたか。
- [ ] 一覧methodを `list(...)` と命名し、SQLを `<entity>_list.sql` に置くと決めたか。
- [ ] ページングを `#[Pager(perPage: 'perPage')]` と `PagesInterface` で扱う前提を理解したか（`perPage` はint固定値と引数名stringの両対応）。
- [ ] `page` / `perPage` の範囲外入力をResource側でclampすると決めたか（`Articles::onGet()` はperPage上限100・範囲外pageは最終ページへ丸める）。

## Source

- [`src/Resource/App/Articles.php::onGet()`](../src/Resource/App/Articles.php)
- [`src/Query/ArticleQueryInterface.php::list()`](../src/Query/ArticleQueryInterface.php)
- [`src/Factory/ArticleFactory.php::fromRows()`](../src/Factory/ArticleFactory.php)
- [`var/db/sql/article_list.sql`](../var/db/sql/article_list.sql)
- [`src/Resource/Page/ArticleList.php::onGet()`](../src/Resource/Page/ArticleList.php)

## Tests

- [`tests/Resource/App/ArticlesTest.php`](../tests/Resource/App/ArticlesTest.php)
- [`tests/Resource/Page/ArticleListTest.php`](../tests/Resource/Page/ArticleListTest.php)
- [`tests/Smoke/MediaQuerySamplesTest.php`](../tests/Smoke/MediaQuerySamplesTest.php)

## Key points

collectionは `list()`、SQLは `article_list.sql`、`perPage` は `#[Pager(perPage: 'perPage')]` と対応する。`count($pages)` でCOUNT SQL、`$pages[$page]` でそのページのSQLが遅延実行される。pager経路の `$pages[$page]->data` はsnake_caseキーの連想配列で返るため、Resource側で `ArticleFactory::fromRows()` によりEntityへ変換する。

## Do not

- `item()` と同じ感覚で `factory:` によるEntity hydrationを期待しない — `#[Pager]` 付きmethodは getPages 経路を通り、`$pages[$page]->data` はsnake_case連想配列のまま返る。Entity化はResource側の `fromRows()` で行う。

## マスター確認（After）

- [ ] 一覧Resourceが item Resourceと別クラスになっている。
- [ ] `list()` method の戻り値が `PagesInterface`、`#[Pager]` の `perPage` 名が parameter 名と一致。
- [ ] filter（categoryId/tagId/status 等）省略時と指定時の件数差を `ArticlesTest.php` 相当で green。
- [ ] perPage上限clampと範囲外pageの最終ページclampを `ArticlesTest.php` の該当ケース相当で green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 対になるitem resource（主キーで1件）
- [`db-entity-factory`](./db-entity-factory.md) — `fromRow()` が委譲するEntity factoryの型
- [`db-result-projection`](./db-result-projection.md) — Query結果を専用Result objectにする
- [`page-resource-list`](./page-resource-list.md) — Page Resourceで一覧HTMLを描画する
- [`hal-link`](./hal-link.md) — 一覧から詳細への `_links` を宣言する
- [`cacheable-response`](./cacheable-response.md) — 一覧レスポンス全体をキャッシュする
