# `db-array-row-comparison`

**Entityではなくarrayで読む比較例を見る** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `comparison-only`
- **Aliases:** array row, no entity, row array, `type: row`, migration comparison, 連想配列, 配列で受け取る, Entityなし
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** Entityを使わない実装と正規形の責務差を理解したい。

## 例

正規形 [`db-read-one-entity`](./db-read-one-entity.md) と同じ GET を、Entity を作らず連想配列のまま実装した比較例。

### QueryInterface

`type: 'row'` 指定で単一行を連想配列で直接受ける（無指定の `array` 戻り値は rowlist になる）:

```php
#[DbQuery('article_as_array_item', type: 'row')]
public function item(int $id): array|null;
```

### Resource

`null` → 404 の流れは正規形と同じ。違いは、型キャスト・キー存在の防衛が Resource 側に寄ること:

```php
public function onGet(int $id): static
{
    $row = $this->article->item($id);
    if ($row === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $id];

        return $this;
    }

    $this->body = $this->toResponseBody($row);

    return $this;
}

/**
 * @param array<string, mixed> $row
 */
private function toResponseBody(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'slug' => (string) $row['slug'],
        'title' => (string) $row['title'],
        'body' => (string) $row['body'],
        'excerpt' => isset($row['excerpt']) ? (string) $row['excerpt'] : null,
        'status' => (string) $row['status'],
        'publishedAt' => $this->normaliseDateTime($row['publishedAt'] ?? null),
        'authorId' => (int) $row['authorId'],
        'categoryId' => (int) $row['categoryId'],
    ];
}
```

日付正規化も Resource が持つことになる:

```php
private function normaliseDateTime(mixed $value): string|null
{
    if ($value === null || $value === '') {
        return null;
    }

    $value = (string) $value;

    $value = str_replace(' ', 'T', $value);
    if (preg_match('/(?:Z|[+\-]\d{2}:\d{2})$/i', $value) === 1) {
        return $value;
    }

    return $value . 'Z';
}
```

### SQL

array のキーは SELECT のカラム名がそのまま使われる — camelCase キーが欲しければ SQL 側で `AS` エイリアスする（Entity 版の位置渡し hydration では不要だった対応）:

```sql
SELECT
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at AS publishedAt,
    author_id AS authorId,
    category_id AS categoryId
FROM articles
WHERE id = :id
LIMIT 1
```

## Naming

比較例でも method 名は正規形と同じ noun-form `item(int $id)` を使い、戻り値だけ `array|null` に変わる:

| 対象 | 名前 |
|---|---|
| Interface | `ArticleAsArrayQueryInterface` — 比較用は `src/Query/Variations/` に置く |
| SQL ファイル | `article_as_array_item.sql` — `<entity>_item.sql` の語彙を保つ |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] これは正規形ではなく**比較学習用**であると理解したか（デフォルト採用しない）。
- [ ] 何を比較したいか（型変換・日付正規化・row shape防衛がどこに寄るか）を意識したか。
- [ ] `#[DbQuery(type: 'row')]` は単一行を連想配列で直接受ける指定であり、無指定の `array` 戻り値はrowlist（単一行でも `$result[0]` に入る）と理解したか。

## Source

- [`src/Resource/App/Variations/ArticleAsArray.php`](../src/Resource/App/Variations/ArticleAsArray.php)
- [`src/Query/Variations/ArticleAsArrayQueryInterface.php`](../src/Query/Variations/ArticleAsArrayQueryInterface.php)
- [`var/db/sql/article_as_array_item.sql`](../var/db/sql/article_as_array_item.sql)

## Tests

- [`tests/Resource/App/Variations/ArticleAsArrayTest.php`](../tests/Resource/App/Variations/ArticleAsArrayTest.php)

## Key points

型変換、日付正規化、row shapeの防衛がResource側に寄ることを確認する。正規形（[`db-read-one-entity`](./db-read-one-entity.md)）ではこれらの責務を Entity と factory（[`db-entity-factory`](./db-entity-factory.md)）が持ち、Resource は Entity のプロパティを body に詰め替えるだけで済む。

## Do not

- 長期保守の標準形としてarray rowを無条件に選ばない — これは比較学習用の型であり、防衛コードがResourceに散る。

## マスター確認（After）

- [ ] array実装で増える防衛コード（型・日付・キー存在）を列挙し、Entity版と比較できた。
- [ ] このパターンを標準採用しない理由を自分の言葉で説明できる。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 正規形（Entityで受ける主キー1件読み）
- [`db-entity-factory`](./db-entity-factory.md) — enum変換・日付正規化を factory に寄せる正規形
- [`db-sqlquery-orchestration`](./db-sqlquery-orchestration.md) — 抽象度軸の比較（宣言的 `#[DbQuery]` vs プログラマティック SqlQuery）
- [`db-raw-pdo-comparison`](./db-raw-pdo-comparison.md) — フレームワーク有無軸の比較（raw PDO）
- [`not-found-response`](./not-found-response.md) — `null` → 404 のidiom（このKataでも同じ）
