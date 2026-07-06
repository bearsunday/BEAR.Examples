# `defer-conditional`

**`DeferInterface::add()`で条件付きdeferを手動制御する** · [← 索引に戻る](../index.md)

- **Category:** Deferred execution
- **Status:** `showcase`
- **Aliases:** conditional defer, DeferInterface, manual defer, add(), flush(), DeferTransfer, ConnectionCloserInterface, 条件付き遅延実行, 手動enqueue, fastcgi_finish_request
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/defer.html
- **Use when:** follow-up処理が条件付きの場合、`#[Defer]` を迂回して `DeferInterface::add()` で手動制御したい。

## 例

### Resource（条件付き手動enqueue）

`#[Defer]` を使わず、`DeferInterface` と `ResourceInterface` をinjectして条件成立時だけenqueueする:

```php
class ConditionalArticle extends ResourceObject
{
    public function __construct(
        private readonly ArticleRepository $repo,
        private readonly ResourceInterface $resource,
        private readonly DeferInterface $defer,
    ) {
    }

    public function onPost(string $title, string $body, bool $publish = false): static
    {
        $id = $this->repo->save($title, $body);
        $this->code = 202;
        $this->body = ['id' => $id];

        if ($publish) {
            $request = $this->resource->newRequest(Method::POST, 'app://self/publish', ['id' => $id]);
            $this->defer->add($request);
        }

        return $this;
    }
}
```

### DeferInterface（external package）

queueへの追加と実行の2 methodだけ。BEAR.Resourceの `Request` はinvokableなので `add()` にそのまま渡せる:

```php
interface DeferInterface
{
    /** @param callable(): mixed $request */
    public function add(callable $request): void;

    public function flush(): void;
}
```

### DeferTransfer（transfer → connection release → flush）

base transferでresponseを送り、connectionをreleaseしてから `flush()`。`finally` なのでclose失敗でもdeferred workは走る:

```php
public function __invoke(ResourceObject $ro, array $server)
{
    ($this->transfer)($ro, $server);
    try {
        ($this->close)();
    } finally {
        $this->defer->flush();
    }
}
```

### SapiConnectionCloser（SAPI別の接続解放）

PHP-FPM → LiteSpeed → その他web SAPIはbest-effort、CLIはno-op:

```php
public function __invoke(): void
{
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();

        return;
    }

    if (function_exists('litespeed_finish_request')) {
        litespeed_finish_request();

        return;
    }

    if (PHP_SAPI !== 'cli') {
        flush(); // best-effort on other web SAPIs (e.g. Apache mod_php)
    }
}
```

### 検証（条件でenqueueが分岐する）

`SpyDefer` で「falseで0件・trueで1件」をpinし、`flush()` で実際に実行されることを確認する:

```php
$ro = $resource->post('app://self/conditional-article', [
    'title' => 'Hi',
    'body' => 'Body',
    'publish' => true,
]);

$this->assertSame(202, $ro->code);
$this->assertCount(1, $spy->added, 'One deferred request when publish=true');

$spy->flush();
$this->assertSame(['publish:100'], $log->calls);
```

## Naming

この型に固有の命名はほぼ無い — 機構は bear/defer（external package）の `DeferInterface` / `ConnectionCloserInterface` をそのまま使う。Resource側の依存プロパティは役割そのままの名詞で:

| 依存 | プロパティ |
|---|---|
| `DeferInterface` | `$defer` |
| `ResourceInterface` | `$resource` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 常にdeferするなら宣言的な `#[Defer]`（[`defer-resource-request`](./defer-resource-request.md)）、runtime条件で分岐する時だけ `add()` を使うと決めたか。
- [ ] `DeferInterface` と `ResourceInterface` をinjectし、`$defer->add($request)` で手動enqueueすると決めたか。
- [ ] `DeferTransfer` がbase transfer後にconnectionをreleaseし、その後に `flush()` が走ることを理解したか。

## Source

- [`vendor/bear/defer/src/DeferInterface.php`](../vendor/bear/defer/src/DeferInterface.php) *(external package)*
- [`vendor/bear/defer/src/DeferTransfer.php`](../vendor/bear/defer/src/DeferTransfer.php) *(external package)*
- [`vendor/bear/defer/src/ConnectionCloserInterface.php`](../vendor/bear/defer/src/ConnectionCloserInterface.php) *(external package)*
- [`vendor/bear/defer/src/SapiConnectionCloser.php`](../vendor/bear/defer/src/SapiConnectionCloser.php) *(external package)*
- [`tests/Fake/Defer/Resource/App/ConditionalArticle.php`](../tests/Fake/Defer/Resource/App/ConditionalArticle.php)
- [`tests/Fake/Defer/SpyDefer.php`](../tests/Fake/Defer/SpyDefer.php)

## Tests

- [`tests/Resource/App/DeferTest.php`](../tests/Resource/App/DeferTest.php)

## Key points

`DeferInterface::add(callable $request)` で手動enqueue — BEAR.Resourceの `Request` はinvokableなのでそのまま渡せる。`DeferTransfer` は transfer → connection release → flush の順で、`flush()` はfinallyで実行される（connection close失敗でもdeferred workは走る）。`SapiConnectionCloser` は `fastcgi_finish_request`（PHP-FPM）→ `litespeed_finish_request` → その他はbest-effort `flush()`、CLIはno-op。

## Do not

- `DeferInterface` のsingleton queueをflushせずに放置しない（`flush()` はrequest boundaryで必須）。本番では `DeferTransfer` がflushするが、custom runnerやtestで `add()` だけ呼ぶとqueueが溜まったまま実行されない。

## マスター確認（After）

- [ ] 条件フラグfalseでenqueueされず、trueで1件enqueueされ `flush()` で実行されることを `DeferTest.php` 相当で green。

## See also

- [`defer-resource-request`](./defer-resource-request.md) — 常にdeferするなら宣言的な `#[Defer]` + `#[Link]`（本Kataの相手方）
- [`async-embed-parallel`](./async-embed-parallel.md) — 応答前の並列実行（応答後に回すdeferとの対比）
- [`stream-response`](./stream-response.md) — transfer層を差し替えるもう1つの型
- [`fake-sql-query`](./fake-sql-query.md) — interfaceをspy/fakeに差し替えてテストする手法（`SpyDefer` と同型）
