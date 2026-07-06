# `db-read-one-entity`

**DBから主キーで1件のEntityを読む** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `canonical`
- **Aliases:** read one row, fetch entity, primary key lookup, item query, `#[DbQuery]`, BDR read, article detail, Ray.MediaQuery, 1件取得, 主キー検索, エンティティ取得
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** 主キーで1件取得し、型付きEntityとしてResourceで使いたい。

## 例

### QueryInterface

素の `#[DbQuery]` — constructor直渡しできるEntity:

```php
#[DbQuery('author_item')]
public function item(int $id): Author|null;
```

`factory:` 指定 — enum・日付正規化が要るEntity（→ [`db-entity-factory`](./db-entity-factory.md)):

```php
#[DbQuery('article_item', factory: ArticleFactory::class)]
public function item(int $id): Article|null;
```

### Entity

constructor引数順が SQL の SELECT カラム順に一致する:

```php
final readonly class Article
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public string $body,
        public string|null $excerpt,
        public ArticleStatus $status,
        public string|null $publishedAt,
        public int $authorId,
        public int $categoryId,
    ) {}
}
```

### Resource

`null` なら 404、そうでなければ Entity のプロパティを body に詰める:

```php
public function onGet(int $id): static
{
    $article = $this->article->item($id);
    if ($article === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $id];

        return $this;
    }

    $this->body = [
        'id' => $article->id,
        'slug' => $article->slug,
        'title' => $article->title,
        'body' => $article->body,
        'excerpt' => $article->excerpt,
        'status' => $article->status->value,
        'publishedAt' => $article->publishedAt,
        'authorId' => $article->authorId,
        'categoryId' => $article->categoryId,
    ];

    return $this;
}
```

### SQL

カラム順が Entity constructor の引数順と1対1で対応する:

```sql
SELECT
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at,
    author_id,
    category_id
FROM articles
WHERE id = :id
LIMIT 1
```

## Naming

Read と Write は interface を分ける — どちらも `src/Query/` に置く:

| 種別 | Interface | 例 |
|---|---|---|
| Read | `<Entity>QueryInterface` | `ArticleQueryInterface` |
| Write | `<Entity>CommandInterface` | `ArticleCommandInterface` |

Read の method 命名:

| 形 | メソッド | SQL ファイル |
|---|---|---|
| 主キーで1件 | `item(int $id)` | `<entity>_item.sql` |
| 自然キーで1件 | `by<NaturalKey>()` | `<entity>_by_<key>.sql` |
| 一覧 | `list()` / `list<Variant>()` | `<entity>_list.sql` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] read用の `<Entity>QueryInterface` を、write用Commandと分けて用意したか。
- [ ] 1件取得methodを `item(int $id): <Entity>|null` のシグネチャにするか決めたか。
- [ ] SQLは `<entity>_item.sql` という命名でファイルに置くと決めたか。
- [ ] `SELECT` のカラム順を hydration 先の引数順（`factory:` 指定時は factory method、無指定時は Entity constructor）に合わせる前提を理解したか（いずれも `PDO::FETCH_FUNC` で位置渡し）。

## Source

- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`src/Query/ArticleQueryInterface.php::item()`](../src/Query/ArticleQueryInterface.php)
- [`src/Query/AuthorQueryInterface.php::item()`](../src/Query/AuthorQueryInterface.php)
- [`src/Entity/Article.php`](../src/Entity/Article.php)
- [`var/db/sql/article_item.sql`](../var/db/sql/article_item.sql)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Resource/Page/ArticleTest.php`](../tests/Resource/Page/ArticleTest.php)

## Key points

`item(int $id): Article|null`、SQLファイルは `article_item.sql`、ResourceはSQLを直接持たない。constructor直渡しできるEntity（`Author` 等）は素の `#[DbQuery]`、enum変換・日付正規化が要るEntityは [`db-entity-factory`](./db-entity-factory.md) の型で `factory:` を指定する。

## Do not

- `null` を throw しない — BEAR.Sundayでは404を `$this->code = Code::NOT_FOUND` で返す。他フレームワーク経験者は例外を投げがちだが、これはidiomではない。

## マスター確認（After）

- [ ] Resource class に SQL 文字列が無い（`grep -i select src/Resource/App/<Name>.php` が空）。
- [ ] Query method が `item(int $id): <Entity>|null` 型を返す。
- [ ] `var/db/sql/<entity>_item.sql` が存在し、`SELECT` カラム順が hydration 先（factory method / Entity constructor）の引数順と一致。
- [ ] `ArticleTest.php` 相当を写経し、存在IDで200・型付きbody、未存在IDで404を pin して green。

## See also

- [`db-entity-factory`](./db-entity-factory.md) — enum・日付正規化が要るEntityのfactory
- [`db-read-by-natural-key`](./db-read-by-natural-key.md) — INSERT後のID回収を自然キーで行う
- [`db-read-list-pager`](./db-read-list-pager.md) — 一覧をページングして読む
- [`db-command-write`](./db-command-write.md) — Read/Write分離の相手方（書き込みCommand）
- [`not-found-response`](./not-found-response.md) — 404のidiom
- [`cacheable-leaf`](./cacheable-leaf.md) — 結果をキャッシュする
- [`hal-link`](./hal-link.md) / [`hal-embed`](./hal-embed.md) — HALでリンク・埋め込みを付ける
