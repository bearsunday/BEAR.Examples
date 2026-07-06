# `db-raw-pdo-comparison`

**Raw PDOとの違いを見る** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `comparison-only`
- **Aliases:** raw PDO, `ExtendedPdoInterface`, inline SQL, framework comparison, MediaQuery responsibility, Aura.Sql, fetchOne, 生PDO, 低レベル比較
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** Ray.MediaQueryが外部化している責務を低レベル比較で理解したい。

## 例

### Resource（Raw PDO版）

正規形 `Article::onGet()`（[`db-read-one-entity`](./db-read-one-entity.md)）と同じGETをMediaQuery無しで書くと、SQL・bind・fetchがResourceに現れる。`ExtendedPdoInterface` はAuraSqlModuleのDIでconstructor注入される。`fetchOne()` は行が無いと `false` を返す:

```php
class ArticleRawPdo extends ResourceObject
{
    private const string SQL = <<<'SQL'
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
SQL;

    public function __construct(
        private readonly ExtendedPdoInterface $pdo,
    ) {
    }

    public function onGet(int $id): static
    {
        $row = $this->pdo->fetchOne(self::SQL, ['id' => $id]);
        if ($row === false) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->body = $this->toResponseBody($row);

        return $this;
    }
}
```

### 型変換・日付正規化

正規形ならhydration（[`db-entity-factory`](./db-entity-factory.md) のfactory）が担う型変換・日付正規化も、手書きのcastとprivate methodになる:

```php
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

### テスト用のFake PDO

正規形のテストは [`fake-sql-query`](./fake-sql-query.md) を使い回せるが、Raw PDO版はfake配線も自前になる。`sqlite::memory:` に `articles` schemaを作り、`var/fake/article.json` の行をINSERTして返すproviderを差し替える:

```php
/** @implements ProviderInterface<ExtendedPdoInterface> */
final class FakeExtendedPdoProvider implements ProviderInterface
{
    public function get(): ExtendedPdoInterface
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        // ...

        return $pdo;
    }
}
```

## Naming

MediaQuery正規形の命名（`item()` / `<entity>_item.sql`）に対し、このvariationでは同じ責務がclass内へ寄る:

| 対象 | MediaQuery正規形 | Raw PDO版 |
|---|---|---|
| SQL | `var/db/sql/article_item.sql` | class定数 `ArticleRawPdo::SQL`（inline） |
| 1件取得 | `ArticleQueryInterface::item(int $id)` | `$pdo->fetchOne(self::SQL, ['id' => $id])` |
| 置き場所 | `src/Resource/App/Article.php` | `src/Resource/App/Variations/ArticleRawPdo.php`（比較専用） |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] これは比較学習用で、正規のApp Resourceにinline SQLを戻さないと理解したか。
- [ ] Ray.MediaQueryが肩代わりしている責務（SQL外部化・bind・fetch・型変換）を意識したか。

## Source

- [`src/Resource/App/Variations/ArticleRawPdo.php`](../src/Resource/App/Variations/ArticleRawPdo.php)
- [`tests/Fake/FakeExtendedPdoProvider.php`](../tests/Fake/FakeExtendedPdoProvider.php)

## Tests

- [`tests/Resource/App/Variations/ArticleRawPdoTest.php`](../tests/Resource/App/Variations/ArticleRawPdoTest.php)

## Key points

SQL、bind、fetch、型変換、日付正規化がResource近くに現れる。`ExtendedPdoInterface` はAuraSqlModuleのDIでconstructor注入される。`fetchOne()` は行が無いと `false` を返すため404分岐は `=== false` になる — MediaQuery正規形（[`db-read-one-entity`](./db-read-one-entity.md)）の `Entity|null` とは欠損の表現が異なる。

## マスター確認（After）

- [ ] Raw PDO版でResourceに現れる5つの責務（SQL/bind/fetch/型変換/日付）を指摘できる。
- [ ] それらがMediaQuery正規形ではどこへ移るか説明できる。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 同じGETのMediaQuery正規形
- [`db-sqlquery-orchestration`](./db-sqlquery-orchestration.md) — `#[DbQuery]` とraw PDOの中間（プログラマティックな `SqlQuery`）
- [`db-array-row-comparison`](./db-array-row-comparison.md) — Entity vs array のもう一つの比較軸
- [`db-entity-factory`](./db-entity-factory.md) — 型変換・日付正規化を正規形で担うfactory
- [`fake-sql-query`](./fake-sql-query.md) — 正規形でのDB無しテスト（Raw PDO版では自前のfake providerが要る）
- [`not-found-response`](./not-found-response.md) — 404のidiom
