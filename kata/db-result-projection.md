# `db-result-projection`

**Query結果を専用Result objectにする** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `showcase`
- **Aliases:** query projection, SELECT result, collection wrapper, typed collection, `PostQueryInterface`, fromContext, PostQueryContext, 型付きコレクション, 射影
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** `list<Entity>` の素配列ではなく、`published()` / `titles()` のような意図を表すnamed methodを持つ型付きcollection wrapperでSELECT結果を返したい。

## 例

### QueryInterface

戻り値をEntityのlistではなくResult objectにする。行のhydrationは `factory:` 指定で通常のEntity読みと同じ（→ [`db-entity-factory`](./db-entity-factory.md)):

```php
#[DbQuery('article_selection_list', factory: ArticleFactory::class)]
public function list(string|null $status = null): ArticleSelection;
```

### Result object

`PostQueryInterface` を実装し、`fromContext()` でhydration済みのrowsを受け取る。named methodが表示意図を表す:

```php
final readonly class ArticleSelection implements PostQueryInterface, IteratorAggregate, Countable
{
    /** @param list<Article> $rows */
    public function __construct(
        public array $rows,
    ) {
    }

    public static function fromContext(PostQueryContext $context): static
    {
        /** @var list<Article> $rows */
        $rows = $context->rows;

        return new self($rows);
    }

    public function published(): self
    {
        return new self(array_values(array_filter(
            $this->rows,
            static fn (Article $article): bool => $article->isPublished(),
        )));
    }

    /** @return list<string> */
    public function titles(): array
    {
        return array_map(static fn (Article $article): string => $article->title, $this->rows);
    }

    public function first(): Article|null
    {
        return $this->rows[0] ?? null;
    }

    public function count(): int
    {
        return count($this->rows);
    }

    /** @return ArrayIterator<int, Article> */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->rows);
    }
}
```

### SQL

`:status` がNULLなら全件、指定時は絞り込み。カラム順は factory method の引数順に一致する:

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
WHERE (:status IS NULL OR a.status = :status)
ORDER BY a.published_at DESC, a.id DESC
```

### 利用側（test）

```php
$articles = $query->list('published');

$this->assertGreaterThan(0, $articles->count());
$this->assertNotSame([], $articles->titles());
$this->assertSame($articles->count(), $articles->published()->count());

$first = $articles->first();
$this->assertInstanceOf(Article::class, $first);
$this->assertTrue($first->isPublished());
$this->assertContainsOnlyInstancesOf(Article::class, iterator_to_array($articles));
```

## Naming

Result wrapperでも Read の命名は通常どおり:

| 対象 | 形 | この Kata の例 |
|---|---|---|
| Query interface | `<対象>QueryInterface` | `ArticleSelectionQueryInterface` |
| 一覧 method | `list()` | `ArticleSelectionQueryInterface::list()` |
| SQL ファイル | `<対象>_list.sql` | `article_selection_list.sql` |
| Result object | `src/Result/` に置く | `ArticleSelection` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 表示用の加工（絞り込み、整形）を汎用Entityやtemplateに入れたくない理由を明確にしたか。
- [ ] Result objectを `PostQueryInterface` 実装にし、named method（`published()` 等）で意図を表すと決めたか。

## Source

- [`src/Query/ArticleSelectionQueryInterface.php`](../src/Query/ArticleSelectionQueryInterface.php)
- [`src/Result/ArticleSelection.php`](../src/Result/ArticleSelection.php)
- [`src/Factory/ArticleFactory.php`](../src/Factory/ArticleFactory.php)
- [`var/db/sql/article_selection_list.sql`](../var/db/sql/article_selection_list.sql)

## Tests

- [`tests/Smoke/MediaQuerySamplesTest.php`](../tests/Smoke/MediaQuerySamplesTest.php)

## Key points

`ArticleSelection::published()` のようなnamed methodでtemplate側の条件分岐を減らす。Result objectは `IteratorAggregate` / `Countable` を実装し、`fromContext()` で構築する。wrapper内の行のhydrationはEntity listと同じ仕組み（ここでは `factory: ArticleFactory::class`）。`AffectedRows` / `InsertedRow` も同じ `PostQueryInterface` 機構の実装であり、カスタムDML resultも同機構で作れる。

## Do not

- presentation専用の加工（`published()` の絞り込み等）を汎用Entityやtemplateに押し込む。表示意図はResult objectのnamed methodに閉じ込める。

## マスター確認（After）

- [ ] Result class が `PostQueryInterface` を実装し、表示意図を表す named method を持つ。
- [ ] template / Resource 側に同じ絞り込みロジックが重複していない。
- [ ] `MediaQuerySamplesTest.php` 相当（`titles()` / `published()->count()` / `first()`）を写経して green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 主キーで1件のEntityを読む基本形
- [`db-entity-factory`](./db-entity-factory.md) — wrapper内の行hydrationを担うfactory
- [`db-read-list-pager`](./db-read-list-pager.md) — 一覧を連想配列rows + Pagerで読む形
- [`db-command-write`](./db-command-write.md) — Read/Write分離の書き込み側（`InsertedRow` / `AffectedRows` も同じ機構）
- [`db-array-row-comparison`](./db-array-row-comparison.md) — Entityではなくarrayで読む比較例
