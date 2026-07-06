# `db-sqlquery-orchestration`

**`SqlQueryInterface`で複数SQLを調停する** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `comparison-only`
- **Aliases:** `SqlQueryInterface`, multi query, previous next article, reading time, programmatic query, getRow, 複数クエリ, 前後記事, 直接実行
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** 1つの `#[DbQuery]` methodに収まらない複数SQLの調停を理解したい。

## 例

### Resource

`#[DbQuery]` の代わりに `SqlQueryInterface` を constructor injection し、Resource が sqlId を指定して複数SQL（本体 + 前後記事）を順に呼び、PHP側で結果（読了時間の算出を含む）を組み立てる:

```php
class ArticleSqlQuery extends ResourceObject
{
    public function __construct(
        private readonly SqlQueryInterface $sqlQuery,
    ) {
    }

    public function onGet(int $id): static
    {
        $row = $this->row('article_sqlquery_item', ['id' => $id]);
        if ($row === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $body = self::articleBody($row);
        $body['readingTimeMinutes'] = self::readingTimeMinutes($body['body']);
        $body['previous'] = null;
        $body['next'] = null;

        $publishedAt = $row['publishedAt'] ?? null;
        if ($publishedAt !== null && $publishedAt !== '') {
            $params = ['id' => $body['id'], 'publishedAt' => $publishedAt];
            $body['previous'] = self::articleSummary($this->row('article_sqlquery_previous', $params));
            $body['next'] = self::articleSummary($this->row('article_sqlquery_next', $params));
        }

        $this->body = $body;

        return $this;
    }
}
```

### getRow wrapper

`getRow()` は `array|object|null` を返すので、連想配列前提を assert で固定する:

```php
private function row(string $sqlId, array $values): array|null
{
    $row = $this->sqlQuery->getRow($sqlId, $values);
    assert($row === null || is_array($row));

    return $row;
}
```

### SQL

Entity hydration を通らないため、`AS publishedAt` 等の SELECT エイリアスがそのまま連想配列のキーになる。前後記事は同一パラメータで方向だけ違う2ファイル — `article_sqlquery_previous.sql`:

```sql
SELECT
    id,
    slug,
    title,
    published_at AS publishedAt
FROM articles
WHERE status = 'published'
  AND published_at IS NOT NULL
  AND (
    published_at < :publishedAt
    OR (published_at = :publishedAt AND id < :id)
  )
ORDER BY published_at DESC, id DESC
LIMIT 1
```

`article_sqlquery_next.sql` は比較演算子（`>`）と ORDER 方向（ASC）を反転した鏡像。

## Naming

sqlId は `var/db/sql/` の SQL ファイル名（拡張子抜き）と1対1:

| 呼び出し | SQL ファイル |
|---|---|
| `getRow('article_sqlquery_item', ...)` | `var/db/sql/article_sqlquery_item.sql` |
| `getRow('article_sqlquery_previous', ...)` | `var/db/sql/article_sqlquery_previous.sql` |
| `getRow('article_sqlquery_next', ...)` | `var/db/sql/article_sqlquery_next.sql` |

comparison-only の variation resource は `src/Resource/App/Variations/` に置き、SQL は正規形の `article_item.sql` と衝突しないよう `article_sqlquery_*` prefix を付ける。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] これは比較学習用で、単純な1件取得には使わないと理解したか。
- [ ] 標準形は Query Interface + `#[DbQuery]`、これは複数SQL調停が必要な時のみと区別できたか。
- [ ] 新しいsqlIdを足す時は `tests/Fake/FakeSqlQuery.php::getRow()` のmatch分岐にも追加すると理解したか。

## Source

- [`src/Resource/App/Variations/ArticleSqlQuery.php`](../src/Resource/App/Variations/ArticleSqlQuery.php)
- [`var/db/sql/article_sqlquery_item.sql`](../var/db/sql/article_sqlquery_item.sql)
- [`var/db/sql/article_sqlquery_previous.sql`](../var/db/sql/article_sqlquery_previous.sql)
- [`var/db/sql/article_sqlquery_next.sql`](../var/db/sql/article_sqlquery_next.sql)

## Tests

- [`tests/Resource/App/Variations/ArticleSqlQueryTest.php`](../tests/Resource/App/Variations/ArticleSqlQueryTest.php)

## Key points

`SqlQueryInterface::getRow()` をResourceが直接呼び、複数queryの結果を組み立てる。`#[DbQuery]` の位置渡しhydration（→ [`db-read-one-entity`](./db-read-one-entity.md)）と違い `getRow()` は連想配列を返すため、bodyのキーは SQL の SELECT エイリアスで決まる。

## Do not

- 単純な1件取得まで `SqlQueryInterface` に寄せない。標準形はQuery Interface + `#[DbQuery]`（→ [`db-read-one-entity`](./db-read-one-entity.md)）。

## マスター確認（After）

- [ ] `SqlQueryInterface` を直接使う妥当な条件（複数SQLの調停）を説明できる。
- [ ] 単純取得を `#[DbQuery]` に戻すべき境界を判断できる。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 標準形：Query Interface + `#[DbQuery]` で1件を読む
- [`db-array-row-comparison`](./db-array-row-comparison.md) — 比較軸その1：Entity class vs 連想配列
- [`db-raw-pdo-comparison`](./db-raw-pdo-comparison.md) — 比較軸その3：MediaQuery vs 素の `ExtendedPdoInterface`
- [`fake-sql-query`](./fake-sql-query.md) — sqlIdごとのfake分岐（`FakeSqlQuery::getRow()`）
- [`db-transactional`](./db-transactional.md) — write側の複数SQLをトランザクションで包む
