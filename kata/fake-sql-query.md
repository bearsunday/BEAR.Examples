# `fake-sql-query`

**DBなしでMediaQueryをFakeする** · [← 索引に戻る](../index.md)

- **Category:** Tests / fake
- **Status:** `support`
- **Aliases:** FakeSqlQuery, no DB test, fake context, test context, hermetic tests, フェイク, テストダブル, DBなしテスト, インメモリDB
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** DBなしでResource、Query、write flowをテストしたい。

## 例

### FakeSqlQuery — in-memory の SqlQueryInterface

`var/fake/*.json` をテーブルとして読み込み、public な `execLog` / `queryLog` でテストから発行内容をassertできる:

```php
final class FakeSqlQuery implements SqlQueryInterface
{
    /** @var list<array{sqlId: string, values: array<string, mixed>, insertedId?: int}> */
    public array $execLog = [];

    /** @var list<array{method: string, sqlId: string, values: array<string, mixed>}> */
    public array $queryLog = [];

    public function __construct(
        string|null $fakeDir = null,
    ) {
        $fakeDir ??= dirname(__DIR__, 2) . '/var/fake';
        $this->tables = [
            'article' => $this->load($fakeDir . '/article.json'),
            'category' => $this->load($fakeDir . '/category.json'),
            // ...
        ];
    }
}
```

### getRow — write SQL id も read 側に来る

`DbQueryInterceptor` は `#[DbQuery]` methodを戻り型でgetRow/getRowListに振り分けるため、writeのsqlIdもここに届く。allowlist（`WRITE_SQL_IDS`）で `mutate()` にdispatchする:

```php
public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null
{
    if (in_array($sqlId, self::WRITE_SQL_IDS, true)) {
        $this->mutate($sqlId, $values);

        return null;
    }

    $this->queryLog[] = ['method' => 'getRow', 'sqlId' => $sqlId, 'values' => $values];

    return match ($sqlId) {
        'article_item' => $this->findArticleById((int) $values['id']),
        'article_by_slug' => $this->findArticleBySlug((string) $values['slug']),
        // ...
        default => throw new LogicException("FakeSqlQuery: unknown row sqlId '{$sqlId}'"),
    };
}
```

### execPostQuery — InsertedRow / AffectedRows

戻り型が `PostQueryInterface`（`InsertedRow` / `AffectedRows` 等）のwriteはexecPostQueryに来る:

```php
$result = $this->mutate($sqlId, $values);

return match ($postQueryClass) {
    InsertedRow::class => new InsertedRow(
        $values,
        isset($result['insertedId']) ? (string) $result['insertedId'] : null,
    ),
    AffectedRows::class => new AffectedRows($result['affectedRows']),
    default => $postQueryClass::fromContext($this->postQueryContext($values, [])),
};
```

### getPages — `#[Pager]` は FakePages で

`#[Pager]` 付きmethodはgetPages/getCountに来る。`FakePages` がPagerfantaの `ArrayAdapter` で本物と同じ `Page` shapeを返す:

```php
return match ($sqlId) {
    'article_list' => new FakePages(
        array_map(fn ($r) => $this->toArticleSqlRow($r), $this->filteredArticles($values)),
        $perPage,
        $queryTemplate,
    ),
    default => throw new LogicException("FakeSqlQuery: unknown pages sqlId '{$sqlId}'"),
};
```

### Module — Fake→Test の2段構成

`FakeModule` がsingleton bind（同一injector内でlogが共有される）:

```php
$this->bind(SqlQueryInterface::class)->to(FakeSqlQuery::class)->in(Scope::SINGLETON);
```

`TestModule` は `FakeModule` の上にsession/PDOのtest用overrideを重ねる:

```php
protected function configure(): void
{
    $this->install(new FakeModule());
    $this->bind(AuthSessionInterface::class)->toProvider(FakeVisitorAuthSessionProvider::class)->in(Scope::SINGLETON);
    $this->bind(ExtendedPdoInterface::class)->toProvider(FakeExtendedPdoProvider::class)->in(Scope::SINGLETON);
}
```

### Test — write contract を pin

addした行が自然キーで読み返せる — Fakeがread/writeの整合性を保つことをassertする:

```php
$result = $query->execPostQuery('article_add', $values, InsertedRow::class);

$this->assertInstanceOf(InsertedRow::class, $result);
$this->assertNotNull($result->id);

$article = $query->getRow('article_by_slug', ['slug' => 'post-query-contract']);
$this->assertInstanceOf(Article::class, $article);
$this->assertSame((int) $result->id, $article->id);
```

## Naming

Fakeクラスは `Fake` prefix で `tests/Fake/`（namespace `BEAR\Kata\Fake`）に置く — `FakeSqlQuery`, `FakePages`。dispatchするsqlIdはSQLファイル名（`<entity>_<verb>`）と同一の語彙:

| 種別 | sqlId 例 | Fake での扱い |
|---|---|---|
| read（1件 / 自然キー / 一覧） | `article_item`, `article_by_slug`, `article_list` | getRow / getRowList の `match` |
| write | `article_add`, `article_update`, `article_delete` | `WRITE_SQL_IDS` → `mutate()` |
| link-table write | `article_tag_clear`, `article_tag_link` | 同上 |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] Fake は便利データではなく Query contract の実行可能な代替であると理解したか。
- [ ] `DbQueryInterceptor` の3分岐を理解したか — `#[Pager]` 付きはgetPages/getCount、戻り型が `PostQueryInterface`（`InsertedRow`/`AffectedRows` 等）はexecPostQuery、それ以外を戻り型でgetRow/getRowListに振り分ける（write idもgetRow/getRowList側で扱う）。

## Source

- [`tests/Fake/FakeSqlQuery.php`](../tests/Fake/FakeSqlQuery.php)
- [`tests/Fake/FakePages.php`](../tests/Fake/FakePages.php)
- [`tests/Smoke/FakeEntityFetch.php`](../tests/Smoke/FakeEntityFetch.php)
- [`tests/Smoke/FakePostQueryRows.php`](../tests/Smoke/FakePostQueryRows.php)
- [`src/Module/FakeModule.php`](../src/Module/FakeModule.php)
- [`src/Module/TestModule.php`](../src/Module/TestModule.php)
- [`var/fake/article.json`](../var/fake/article.json)

## Tests

- [`tests/Smoke/FakeSqlQueryTest.php`](../tests/Smoke/FakeSqlQueryTest.php)
- [`tests/Smoke/MediaQuerySmokeTest.php`](../tests/Smoke/MediaQuerySmokeTest.php)

## Key points

default PHPUnitはDBなしで動く。Fakeは便利データではなくQuery contractの実行可能な代替。`FakeSqlQuery` はpublicな `execLog` / `queryLog` を持ち、発行されたsqlIdとvaluesをテストからassertできる（`FakeModule` がsingleton bindするため同一injector内で共有）。`TestModule` は `FakeModule` の上にsession/PDOのtest用overrideを重ねる2段構成。

## Do not

- すぐmockに逃げない — 既存Fakeの意味を保つ。mockはmethod単位の期待値を固定するだけで、「addした行が `by_slug` で読み返せる」というQuery contractの整合性が失われる。

## マスター確認（After）

- [ ] 主要テストがDBなしで green（`vendor/bin/phpunit`）。
- [ ] Fake が read/write の両SQL idを Query contract 通りに扱う。
- [ ] `FakeSqlQueryTest.php` 相当が green。

## See also

- [`app-resource-test`](./app-resource-test.md) — このFakeの上でAppリソースをテストする
- [`page-resource-test`](./page-resource-test.md) — Page/HTMLテストも同じFakeで動く
- [`mysql-integration-test`](./mysql-integration-test.md) — Fakeで担保できない部分を実DBで検証する対
- [`semantic-fake-data`](./semantic-fake-data.md) — Fakeの元データ `var/fake/*.json` の生成
- [`db-command-write`](./db-command-write.md) — Fakeが代替するwrite側の本物のcontract
- [`db-read-list-pager`](./db-read-list-pager.md) — getPages/FakePagesが支えるページング型
