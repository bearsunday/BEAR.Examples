# `defer-resource-request`

**`#[Defer]` + `#[Link]`で応答後に実行するfollow-up Resourceを宣言する** · [← 索引に戻る](../index.md)

- **Category:** Deferred execution
- **Status:** `showcase`
- **Aliases:** defer, deferred, #[Defer], 202 Accepted, post-response execution, DeferModule, DeferInterceptor, SyncDefer, 遅延実行, 応答後実行, 非同期follow-up
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/defer.html
- **Use when:** Resourceが202 Acceptedを即時返却し、重いfollow-up処理をレスポンス転送後に実行したい。

## 例

### Resource（宣言側）

`#[Defer]` は同じ method の `#[Link]` rel を参照する。Resource は 202 を即時返し、follow-up は interceptor が enqueue する:

```php
#[Defer(['publish', 'note'])]
#[Link(rel: 'publish', href: 'app://self/publish{?id}', method: 'post')]
#[Link(rel: 'note', href: 'app://self/note{?id}', method: 'post')]
public function onPost(string $title, string $body): static
{
    $id = $this->repo->save($title, $body);
    $this->code = 202;
    $this->body = ['id' => $id];

    return $this;
}
```

### Follow-up Resource

通常のResource — 自分がdeferされていることを知らない:

```php
class Publish extends ResourceObject
{
    public function onPost(string $id): static
    {
        $this->log->call("publish:{$id}");

        return $this;
    }
}
```

### Module（DeferModuleのbinding）

queueはsingleton、`#[Defer]` methodにinterceptor、`TransferInterface` を `DeferTransfer` でdecorateする:

```php
$this->bind(DeferInterface::class)->to(SyncDefer::class)->in(Scope::SINGLETON);
$this->bind(ConnectionCloserInterface::class)->to(SapiConnectionCloser::class);
$this->bindInterceptor(
    $this->matcher->any(),
    $this->matcher->annotatedWith(Defer::class),
    [DeferInterceptorInterface::class],
);
$this->rename(TransferInterface::class, 'inner');
$this->bind(TransferInterface::class)->to(DeferTransfer::class);
```

### 仕組み — DeferInterceptor

`proceed()` でbodyを確定させてから、relごとに `#[Link]` hrefをbodyで展開してenqueueする:

```php
$ro = $invocation->proceed();
$links = $this->linkMap($method->getAttributes(Link::class));
foreach ($defer->rels as $rel) {
    $link = $links[$rel] ?? throw new LinkRelNotFoundException($rel);
    $uri = uri_template($link->href, (array) $ro->body);
    $this->defer->add($resource->newRequest(Method::from($link->method), $uri));
}
```

### 仕組み — SyncDefer::flush()

queueを先にクリアしてから全requestを実行し、失敗は集約してthrowする:

```php
public function flush(): void
{
    $queue = $this->queue;
    $this->queue = [];
    $errors = [];
    foreach ($queue as $request) {
        try {
            $request();
        } catch (Throwable $e) {
            $errors[] = $e;
        }
    }

    if ($errors !== []) {
        throw new DeferFlushException($errors);
    }
}
```

## Naming

`#[Defer]` に渡すrelは同じ method の `#[Link]` relと一致させる（無ければ `LinkRelNotFoundException`）。`#[Link]` relはALPS Choreography層の遷移動詞で命名する（`goArticleList`, `doCreateArticle` 等）— `#[Embed]` のTaxonomy名詞と混ぜない。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[Defer(['rel1', 'rel2'])]` で `#[Link]` relを指定し、hardcoded URIを使わないと決めたか。
- [ ] follow-up Resourceは通常のResourceであり、deferを意識しないと理解したか。
- [ ] enqueue実行戦略（sync/queue等）は `DeferInterface` binding、SAPI別の接続解放は `ConnectionCloserInterface` binding（Swooleは丸ごと差し替え）で切り替え、Resource codeは変えないと理解したか。
- [ ] 早期返却が保証されるのはPHP-FPM / LiteSpeedのみで、Apache mod_phpはbest-effort（BEAR.DeferはmanualでAlpha表記）と理解したか。

## Source

- [`vendor/bear/defer/src/Attribute/Defer.php`](../vendor/bear/defer/src/Attribute/Defer.php) *(external package)*
- [`vendor/bear/defer/src/DeferInterceptor.php`](../vendor/bear/defer/src/DeferInterceptor.php) *(external package)*
- [`vendor/bear/defer/src/Module/DeferModule.php`](../vendor/bear/defer/src/Module/DeferModule.php) *(external package)*
- [`vendor/bear/defer/src/SyncDefer.php`](../vendor/bear/defer/src/SyncDefer.php) *(external package)*
- [`tests/Fake/Defer/Resource/App/Article.php`](../tests/Fake/Defer/Resource/App/Article.php)
- [`tests/Fake/Defer/Resource/App/Publish.php`](../tests/Fake/Defer/Resource/App/Publish.php)
- [`tests/Fake/Defer/SpyDefer.php`](../tests/Fake/Defer/SpyDefer.php)

## Tests

- [`tests/Resource/App/DeferTest.php`](../tests/Resource/App/DeferTest.php)

## Key points

`#[Defer]` は `#[Link]` relを参照し、`uri_template($link->href, (array) $ro->body)` でbodyからURIを展開（relが `#[Link]` に無ければ `LinkRelNotFoundException`）。installは既存responder moduleをwrapする形：`$this->install(new DeferModule(new YourHttpResponderModule()))`。`SyncDefer::flush()` はqueueを先にクリアしてから全requestを実行し、失敗を集約して throw する（1件の失敗が残りを止めない）。

## Do not

- 確実な実行やリトライが必要な処理（課金・在庫・失えない記録）をdeferしない — job queueを使う。

## マスター確認（After）

- [ ] 202 が即時返却され、`#[Defer]` relがbodyから展開されたresolved requestとしてenqueueされ、`flush()` で実行されることを `DeferTest.php` 相当で green。

## See also

- [`defer-conditional`](./defer-conditional.md) — runtime条件で分岐する時の手動 `DeferInterface::add()` 版（DeferTransfer / ConnectionCloserの詳細もこちら）
- [`hal-link`](./hal-link.md) — `#[Defer]` が参照する `#[Link]` の宣言
- [`cache-purge`](./cache-purge.md) — write時のfollow-up（cache無効化）を属性で宣言するもう一つの型
- [`stream-response`](./stream-response.md) — response転送層に関わるもう一つの型（ストリーム転送）
