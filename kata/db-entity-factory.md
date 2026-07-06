# `db-entity-factory`

**`#[DbQuery(factory:)]`でDB行をEntityへ変換する** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `canonical`
- **Aliases:** entity factory, DbQuery factory, `factory:`, enum hydration, date normalization, ArticleFactory, fromRow, fromRows, row mapping, ファクトリ変換, hydration
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** DB行をconstructor直渡しできないEntity（enum・日付正規化・派生値を持つ）に変換したい。

## 例

### QueryInterface

`factory:` を指定すると hydration が factory method に委譲される:

```php
#[DbQuery('article_item', factory: ArticleFactory::class)]
public function item(int $id): Article|null;

#[DbQuery('article_by_slug', factory: ArticleFactory::class)]
public function bySlug(string $slug): Article|null;
```

### Factory

`factory()` の引数順が SQL の SELECT カラム順に一致する（`PDO::FETCH_FUNC` の位置渡し）。enum再構築・日付正規化をここに集約する:

```php
final readonly class ArticleFactory
{
    public function factory(
        int $id,
        string $slug,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
        int $authorId,
        int $categoryId,
    ): Article {
        return new Article(
            $id,
            $slug,
            $title,
            $body,
            $excerpt,
            ArticleStatus::from($status),
            self::normaliseDateTime($publishedAt),
            $authorId,
            $categoryId,
        );
    }
}
```

DB文字列から再構築する string-backed enum:

```php
enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
```

### 日付正規化

DBの `YYYY-MM-DD HH:MM:SS` を RFC3339（`YYYY-MM-DDTHH:MM:SSZ`）へ。JSON Schema の `format: date-time` 検証を両Read経路（実DB / Fake）で通すため:

```php
private static function normaliseDateTime(string|null $value): string|null
{
    if ($value === null || $value === '') {
        return null;
    }

    if (str_contains($value, 'T')) {
        return $value;
    }

    return str_replace(' ', 'T', $value) . 'Z';
}
```

### fromRow / fromRows

Pager経由のlistは snake_case キーの連想配列rowで返るため、位置渡しの `factory()` に橋渡しする:

```php
/**
 * @param list<array<string, mixed>> $rows
 *
 * @return list<Article>
 */
public function fromRows(array $rows): array
{
    return array_map(fn (array $row): Article => $this->fromRow($row), $rows);
}

/** @param array<string, mixed> $row */
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

## Naming

| 対象 | 命名 | 例 |
|---|---|---|
| Factory class | `src/Factory/<Entity>Factory.php` | `ArticleFactory` |
| 単一row変換 | `factory()`（引数順=SELECTカラム順） | `ArticleFactory::factory()` |
| 連想配列row変換 | `fromRow()` / `fromRows()` | Pager list用 |

`factory:` を付ける query method 自体の命名は [`db-read-one-entity`](./db-read-one-entity.md) と同じ（`item` / `by<NaturalKey>` / `list`、SQLファイルは `<entity>_item.sql` 等）。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] Entityがenum（`ArticleStatus::from()`）や日付正規化などDB行に無い変換を必要とするか確認したか（不要なら素の `#[DbQuery]` — [`db-read-one-entity`](./db-read-one-entity.md) の型）。
- [ ] 変換をResourceやEntityではなく `src/Factory/<Entity>Factory.php` に置くと決めたか。
- [ ] `factory()` メソッドの引数順をSELECTカラム順に合わせる前提を理解したか（素のfetchのconstructor引数順ルールがfactoryメソッドに移る）。
- [ ] Pager経由のlist（snake_caseキーの連想配列row）用に `fromRow()` / `fromRows()` を用意するか決めたか。

## Source

- [`src/Factory/ArticleFactory.php`](../src/Factory/ArticleFactory.php)
- [`src/Query/ArticleQueryInterface.php::item()`](../src/Query/ArticleQueryInterface.php)
- [`src/Entity/ArticleStatus.php`](../src/Entity/ArticleStatus.php)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Integration/ArticleMySQLTest.php`](../tests/Integration/ArticleMySQLTest.php)

## Key points

`#[DbQuery('article_item', factory: ArticleFactory::class)]` でhydrationをfactoryに委譲（fetchは `FetchInjectionFactory`、同じく `PDO::FETCH_FUNC` の位置渡し）。`factory()` は単一row（引数順=SELECTカラム順）、`fromRows()` はPager由来の連想配列list用。enum再構築・日付正規化はfactory 1箇所に集約する。

## Do not

- 素のconstructor fetchで足りるEntity（`Author`/`Category` 等）にfactoryを増やさない — factoryはenum・日付正規化など変換が要る場合だけの型（不要なら [`db-read-one-entity`](./db-read-one-entity.md)）。

## マスター確認（After）

- [ ] enum/日付変換が factory に集約され、Resource側に `::from(` や日付整形が無い。
- [ ] `factory()` の引数順が `<entity>_item.sql` のSELECTカラム順と一致。
- [ ] `ArticleTest.php` 相当で型付きEntity（enum property含む）が返ることを green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 素の `#[DbQuery]` の基本形（factory不要ならこちら）
- [`db-read-by-natural-key`](./db-read-by-natural-key.md) — 同じfactoryを `bySlug` 等の自然キー読み取りでも使う
- [`db-read-list-pager`](./db-read-list-pager.md) — Pager経由のlistが `fromRows()` の変換元
- [`json-schema-validation`](./json-schema-validation.md) — 日付をRFC3339へ正規化する理由（`format: date-time` 検証）
- [`mysql-integration-test`](./mysql-integration-test.md) — 実DBのdatetimeが正規化されることを pin するテスト
