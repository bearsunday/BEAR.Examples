# `mysql-integration-test`

**実DB経路を必要時だけ検証する** · [← 索引に戻る](../index.md)

- **Category:** Tests / fake
- **Status:** `support`
- **Aliases:** MySQL integration, real DB test, migrations, seed, skip when unavailable, 統合テスト, 実DBテスト, マイグレーション, シード
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** SQL、migration、real backendの代表経路を確認したい。

## 例

### 接続不可ならfailでなくskip

接続先は `MYSQL_TEST_DSN` / `MYSQL_TEST_USER` / `MYSQL_TEST_PASSWORD` で上書き可能。届かなければ suite ごと skip する:

```php
$dsn = getenv('MYSQL_TEST_DSN') !== false ? (string) getenv('MYSQL_TEST_DSN') : 'mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4';
$user = getenv('MYSQL_TEST_USER') !== false ? (string) getenv('MYSQL_TEST_USER') : 'root';
$password = getenv('MYSQL_TEST_PASSWORD') !== false ? (string) getenv('MYSQL_TEST_PASSWORD') : '';

try {
    $this->pdo = new PDO($dsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    $this->markTestSkipped(sprintf('MySQL not reachable at %s: %s', $dsn, $e->getMessage()));
}
```

### DB接続envの退避とtearDown復元

setUpで `DB_*` を書き換える前に旧値を退避し、tearDownで復元する — 同一プロセスの後続（非MySQL）suiteへ漏らさない:

```php
// Snapshot prior values so tearDown() can restore them; otherwise these
// putenv calls leak DB_* into subsequent (non-MySQL) suites in the same process.
foreach (['DB_DSN', 'DB_USER', 'DB_PASSWORD'] as $name) {
    $this->previousEnv[$name] = getenv($name);
}

putenv('DB_DSN=' . $dsn);
putenv('DB_USER=' . $user);
putenv('DB_PASSWORD=' . $password);
```

```php
protected function tearDown(): void
{
    foreach ($this->previousEnv as $name => $value) {
        putenv($value === false ? $name : sprintf('%s=%s', $name, $value));
    }

    $this->previousEnv = [];
}
```

### setUp毎に全テーブルdrop → migrate → seed

毎テスト状態をリセットする。migrate は `migrations.php` 設定の doctrine-migrations、seed は `var/fake/*.json` を流し込む `bin/seed.php`:

```php
$this->pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['article_tags', 'articles', 'tags', 'categories', 'auth_identities', 'authors', 'media', 'doctrine_migration_versions'] as $t) {
    $this->pdo->exec(sprintf('DROP TABLE IF EXISTS `%s`', $t));
}

$this->pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
```

migrate / seed はサブプロセスで実行する。実行中のテストと同じインタプリタを `PHP_BINARY` で固定する:

```php
$cmd = sprintf(
    'cd %s && DB_DSN=%s DB_USER=%s DB_PASSWORD=%s %s vendor/bin/doctrine-migrations migrate --no-interaction 2>&1',
    escapeshellarg(dirname(__DIR__, 2)),
    escapeshellarg(getenv('DB_DSN')),
    escapeshellarg(getenv('DB_USER')),
    escapeshellarg(getenv('DB_PASSWORD')),
    escapeshellarg(PHP_BINARY),
);
exec($cmd, $output, $code);
if ($code !== 0) {
    $this->fail('doctrine-migrations migrate failed: ' . implode("\n", $output));
}
```

### 実SqlQuery経路のcontextとテスト

context は `hal-api-app`（Fakeでなく実SqlQuery経路）。env とスキーマ状態を拾うため毎テスト新しい injector を作る:

```php
// Use 'hal-api-app' to exercise the real SqlQuery path (not Fake).
// Fresh injector each test so env vars and schema state are picked up.
$injector = Injector::getInstance('hal-api-app');
$this->resource = $injector->getInstance(ResourceInterface::class);
```

テストは基底を継承し、seed済みデータの read と write round-trip を pin する:

```php
final class ArticleMySQLTest extends AbstractMySQLTestCase
{
    public function testReadAgainstRealDb(): void
    {
        $ro = $this->resource->get('app://self/article', ['id' => 1]);
        $this->assertSame(200, $ro->code);
        $this->assertSame('getting-started-with-bear-sunday', $ro->body['slug']);
    }
}
```

## Naming

| 対象 | 命名 | 例 |
|---|---|---|
| 置き場所 | `tests/Integration/` | — |
| 基底クラス | `AbstractMySQLTestCase` | — |
| テストクラス | `<Entity>MySQLTest` | `ArticleMySQLTest` |
| 接続上書きenv | `MYSQL_TEST_DSN` / `MYSQL_TEST_USER` / `MYSQL_TEST_PASSWORD` | — |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] default test suite は hermetic に保ち、MySQL integration は接続不可なら skip すると決めたか。
- [ ] 全開発者にMySQL起動を必須にしないと理解したか。
- [ ] setUpで書き換えたDB接続envはtearDownで復元し、後続の非MySQL suiteに漏らさない構造を保つと理解したか。

## Source

- [`tests/Integration/AbstractMySQLTestCase.php`](../tests/Integration/AbstractMySQLTestCase.php)
- [`tests/Integration/ArticleMySQLTest.php`](../tests/Integration/ArticleMySQLTest.php)
- [`migrations.php`](../migrations.php)
- [`bin/seed.php`](../bin/seed.php)

## Tests

- [`tests/Integration/ArticleMySQLTest.php`](../tests/Integration/ArticleMySQLTest.php)
- [`tests/Integration/AuthorMySQLTest.php`](../tests/Integration/AuthorMySQLTest.php)
- [`tests/Integration/CategoryMySQLTest.php`](../tests/Integration/CategoryMySQLTest.php)
- [`tests/Integration/TagMySQLTest.php`](../tests/Integration/TagMySQLTest.php)
- [`tests/Integration/MediaMySQLTest.php`](../tests/Integration/MediaMySQLTest.php)

## Key points

default test suiteはhermetic。MySQL integrationは接続不可ならskipする。接続先は `MYSQL_TEST_DSN` / `MYSQL_TEST_USER` / `MYSQL_TEST_PASSWORD` で上書き可能。setUp毎に全テーブルdrop → migrate → seedで状態をリセットし、contextは `hal-api-app`（Fakeでなく実SqlQuery経路）を使う。

## Do not

- migrate / seed のサブプロセスをシステムの `php` に任せない — `PHP_BINARY` で実行中のテストと同じインタプリタに固定する。システムの `php` が composer.json の `php` 要件より古いと composer の platform_check が発火して migrate が失敗する。

## マスター確認（After）

- [ ] MySQL不在時にintegration testが fail ではなく skip する。
- [ ] MySQL起動時に migration+seed 経由で代表CRUDが green。

## See also

- [`fake-sql-query`](./fake-sql-query.md) — 既定suiteをhermeticに保つDBなしのFake経路（本Kataの相手方）
- [`app-resource-test`](./app-resource-test.md) — App ResourceのAPI contractをDBなしで固定する
- [`page-resource-test`](./page-resource-test.md) — Page ResourceのHTML contractをDBなしで固定する
- [`hypermedia-workflow-test`](./hypermedia-workflow-test.md) — rel遷移を辿るworkflowテスト
- [`db-read-one-entity`](./db-read-one-entity.md) — 実DBで検証されるReadの正規形
- [`db-command-write`](./db-command-write.md) — 実DBで検証されるWriteの正規形
