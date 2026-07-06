# `semantic-fake-data`

**semantic-exで決定的fake dataを作る** · [← 索引に戻る](../index.md)

- **Category:** Semantic / generated artifacts
- **Status:** `support`
- **Aliases:** fake data, semantic-ex, deterministic data, observations, seed source, フェイクデータ, 決定的データ, シードデータ, 参照整合性
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** DBなしテストとreal DB seedの両方で使う代表データを生成したい。

## 例

### 決定的生成（Phase 1）

`composer fake` が `bin/semantic-ex/gen-fake.php` を実行する。`mt_srand(42)` で seed するため、何度実行しても `var/fake/*.json` は同一になる:

```php
mt_srand(42);

$out = dirname(__DIR__, 2) . '/var/fake';
```

参照整合性 — Article の `authorId` / `categoryId` は必ず実在する Author / Category の id を指す:

```php
$articles[] = [
    'id' => $i,
    'slug' => $slug,
    'title' => $title,
    'body' => $body,
    'excerpt' => $excerpt,
    'status' => $status,
    'publishedAt' => $publishedAt,
    'authorId' => (($i - 1) % count($authors)) + 1,
    'categoryId' => (($i - 1) % count($categories)) + 1,
];
```

### Supplemental fixture（id=51）

Page escaping 検証用の fixture は、50件の semantic corpus とは別枠で末尾に追加する:

```php
$articleListArticles = $articles;
// Supplemental Page/Qiq escaping fixture; not part of the 50-record semantic corpus.
$articles[] = [
    'id' => 51,
    'slug' => 'xss-regression',
    'title' => '<script>alert("xss")</script>Bad',
    'body' => 'Plain text body for XSS regression test.',
    'excerpt' => 'Excerpt with <em>html</em> and & ampersand.',
    'status' => 'draft',
    'publishedAt' => '2020-01-01T00:00:00Z',
    'authorId' => 1,
    'categoryId' => 1,
];
```

schema 導出側（`gen-schemas.php`）ではこの fixture を除外してから観察する:

```php
$articles = array_values(array_filter(
    $articles,
    static fn (array $article): bool => ($article['slug'] ?? null) !== 'xss-regression',
));
```

### Observations（Phase 2）

`observations.md` は `gen-fake.php` ではなく `gen-schemas.php`（`composer schema`）が生成する。制約を事前に決めるのではなく、データを観察して導くための記録:

```markdown
# Fake data observations (semantic-ex Phase 2)

Generated from var/fake/*.json — 50 records per atomic entity.

## Article
- `slug` (str): min_len=3 max_len=48 typical=32 nulls=0/50
- `excerpt` (str): min_len=5 max_len=140 typical=140 nulls=0/50
- `publishedAt` (str): min_len=20 max_len=20 typical=20 nulls=8/50
```

### Seed — real DBへの共通入力

`bin/seed.php` は同じ `var/fake/*.json` を real DB に流し込む。Fake と real seed が同一データになる:

```php
$fakeDir = dirname(__DIR__) . '/var/fake';
$loadJson = static fn (string $name) => json_decode(
    (string) file_get_contents($fakeDir . '/' . $name),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

$articles = $loadJson('article.json');
foreach ($articles as $row) {
    $conn->insert('articles', [
        'id' => $row['id'],
        'slug' => $row['slug'],
        'title' => $row['title'],
        'body' => $row['body'],
        'excerpt' => $row['excerpt'] ?? null,
        'status' => $row['status'],
        'published_at' => $publishedAt,
        'author_id' => $row['authorId'],
        'category_id' => $row['categoryId'],
    ]);
}
```

## Naming

fake ファイルは ALPS の state 名、フィールドは descriptor 名に合わせる:

| 対象 | 命名 | 例 |
|---|---|---|
| entity fake | `var/fake/<state>.json` | `article.json`, `author.json` |
| list fake | `<state>List.json` | `articleList.json`, `tagList.json` |
| フィールド | ALPS descriptor と同じ camelCase | `publishedAt`, `mimeType` |
| 参照キー | `<entity>Id`（他entityのidentityを指す） | `authorId`, `categoryId`, `parentId` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] fake data を決定的（`mt_srand(42)`）かつ参照整合性ありで生成すると決めたか。
- [ ] 同じfakeを no-DB テストと real seed の共通入力にすると理解したか。

## Source

- [`bin/semantic-ex/gen-fake.php`](../bin/semantic-ex/gen-fake.php)
- [`bin/semantic-ex/gen-schemas.php`](../bin/semantic-ex/gen-schemas.php)
- [`var/fake/article.json`](../var/fake/article.json)
- [`var/fake/author.json`](../var/fake/author.json)
- [`var/fake/observations.md`](../var/fake/observations.md)
- [`bin/seed.php`](../bin/seed.php)

## Tests

- [`tests/Smoke/FakeSqlQueryTest.php`](../tests/Smoke/FakeSqlQueryTest.php)
- [`tests/Smoke/SqlSmokeTest.php`](../tests/Smoke/SqlSmokeTest.php)

## Key points

fake dataは決定的で、参照整合性を持ち、Fakeとreal seedの共通入力になる。`observations.md` は `gen-fake.php` ではなく `gen-schemas.php`（Phase 2）が生成し `composer schema` で更新される。id=51 `xss-regression` はPage escaping検証用のsupplemental fixtureで、schema導出時には除外される。

## Do not

- testごとに意味の違うfixtureを散らさない — 代表データは `var/fake/*.json` の1つの決定的corpusに集約する。testが個別データを持ち始めると、no-DBテストとreal seedの等価性が崩れる。

## マスター確認（After）

- [ ] `composer fake` を2回実行しても出力 `var/fake/*.json` が同一（決定的）。
- [ ] 同じfakeで no-DB テストと seed が成立する。

## See also

- [`fake-sql-query`](./fake-sql-query.md) — このfake dataをno-DBコンテキストで供給するFake実装
- [`json-schema-generated`](./json-schema-generated.md) — 観察したfake dataからJSON Schemaを導出する（Phase 2+3）
- [`alps-profile-ssot`](./alps-profile-ssot.md) — fake dataの上流にある設計のSSOT
- [`app-resource-test`](./app-resource-test.md) — fakeで動くDBなしのAppリソーステスト
- [`mysql-integration-test`](./mysql-integration-test.md) — seed済みreal DBに対する統合テスト
