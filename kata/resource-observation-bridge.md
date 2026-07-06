# `resource-observation-bridge`

**BEAR.Resource実行からSemantic Logger観察ログを生成する** · [← 索引に戻る](../index.md)

- **Category:** Event Sourcing
- **Status:** `showcase`
- **Aliases:** ResourceObservationModule, InvokerInterface, BodyStoreInterface, FileBodyStore, DevLogModule, observation bridge, SemanticLogInvoker, リソース観察, セマンティックログ
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** BEAR.Resourceの実行ツリーをSemantic Logger観察ログとして記録し、event extractionの入力にしたい。

## 例

### Module — `InvokerInterface` をdecorate

`ResourceObservationModule` を既存moduleに重ねるだけで観察が始まる（`ResourceObservationTest.php` より）:

```php
$module = new ResourceObservationModule(
    methods: new RecordedMethods(RecordedMethods::WITH_READS),
    module: new ResourceModule('BEAR\\Kata\\Fake\\Observation'),
);

$injector = new Injector($module);
$resource = $injector->getInstance(ResourceInterface::class);
```

decorateの実体は `rename` + `toConstructor` — 元のInvokerを別名に退避し、`SemanticLogInvoker` でwrapする:

```php
$this->rename(InvokerInterface::class, self::INVOKER);
$this->bind(InvokerInterface::class)
    ->toConstructor(SemanticLogInvoker::class, ['invoker' => self::INVOKER])
    ->in(Scope::SINGLETON);
```

### SemanticLogInvoker

対象外methodは素通し、対象methodは open → invoke → close。resource例外時もcloseを記録してからrethrowする:

```php
public function invoke(AbstractRequest $request): ResourceObject
{
    $method = $this->recordedMethods->normalize($request->method->value);
    if ($method === null) {
        return $this->invoker->invoke($request);
    }

    $openId = $this->logger->open(new ResourceRequestContext(
        uri: $request->toUri(),
        method: $method,
        params: $request->query,
        timestamp: (new DateTimeImmutable())->format(self::TIMESTAMP_FORMAT),
    ));

    try {
        $ro = $this->invoker->invoke($request);
    } catch (Throwable $e) {
        $this->logger->close(
            new ResourceResponseContext(code: self::httpCode($e), exception: self::exceptionContext($e)),
            $openId,
        );

        throw $e;
    }

    $this->logger->close($this->responseContext($request, $ro), $openId);

    return $ro;
}
```

BodyStore失敗も完了済みrequestを壊さない — 実codeを保ち、失敗を記録するだけ:

```php
private function responseContext(AbstractRequest $request, ResourceObject $ro): ResourceResponseContext
{
    try {
        $bodyRef = ($this->bodyStore)($request, $ro);
    } catch (Throwable $e) {
        // Observation must not break a completed request: keep the real code and record the failure.
        return new ResourceResponseContext(code: $ro->code, exception: self::exceptionContext($e));
    }

    return new ResourceResponseContext($ro->code, $bodyRef);
}
```

### BodyStoreInterface

rendered bodyを外部化し `body_ref` で参照するport。未指定時のデフォルトは `NullBodyStore`（body外部化なし）:

```php
interface BodyStoreInterface
{
    /**
     * Store a response body and return its reference.
     *
     * @return non-empty-string|null
     */
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null;
}
```

### 観察ログ → Event抽出

flushしたlogが [`event-extraction`](./event-extraction.md) の入力になる:

```php
$ro = $resource->get('app://self/hello', ['name' => 'Kata']);

$logger = $injector->getInstance(SemanticLoggerInterface::class);
assert($logger instanceof SemanticLogger);
$log = $logger->flush();

$events = (new SemanticLogExtractor(
    new RecordedMethods(RecordedMethods::WITH_READS),
))->extract($log);
```

## Naming

観察bridgeは新しい命名規則を持ち込まない。構成要素の役割と名前を押さえる:

| 役割 | 名前 |
|---|---|
| decorate対象 | `InvokerInterface`（BEAR.Resource） |
| wrapするInvoker | `SemanticLogInvoker` |
| installするModule | `ResourceObservationModule`（本番）/ `DevLogModule`（開発） |
| body外部化port | `BodyStoreInterface`（実装: `FileBodyStore` / `NullBodyStore`） |
| 記録対象methodの制御 | `RecordedMethods`（`STATE_CHANGING` / `WITH_READS`） |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `ResourceObservationModule` で `InvokerInterface` をdecorateし、`LoggerInterface` はdecorateしないと理解したか。
- [ ] `BodyStoreInterface` でrendered bodyを外部化し、`body_ref` で参照すると決めたか（未指定時のデフォルトは `NullBodyStore` = body外部化なし）。
- [ ] 開発時は `DevLogModule` でbodyファイルを自動クリア＋全method記録すると理解したか。
- [ ] 観察側も `RecordedMethods` で対象を絞る（デフォルトはwrite系のみ。GET観察は `WITH_READS`）と理解したか。

## Source

- [`vendor/bear/event-sourcing/src/Resource/ResourceObservationModule.php`](../vendor/bear/event-sourcing/src/Resource/ResourceObservationModule.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Resource/SemanticLogInvoker.php`](../vendor/bear/event-sourcing/src/Resource/SemanticLogInvoker.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Resource/BodyStoreInterface.php`](../vendor/bear/event-sourcing/src/Resource/BodyStoreInterface.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Resource/NullBodyStore.php`](../vendor/bear/event-sourcing/src/Resource/NullBodyStore.php) *(external package)*
- [`tests/Fake/Observation/Resource/App/Hello.php`](../tests/Fake/Observation/Resource/App/Hello.php)

## Tests

- [`tests/Smoke/ResourceObservationTest.php`](../tests/Smoke/ResourceObservationTest.php)

## Key points

`InvokerInterface` decorate（`rename` + `toConstructor` で元のInvokerを退避してwrap）で観察ログ生成。`BodyStoreInterface` でbody外部化。`DevLogModule` は開発用（全method記録＋自動クリア）。観察はrequestを壊さない — BodyStore失敗時も実codeを保ち、resource例外時もcloseを記録してrethrowする。bridgeのclose contextは `body` でなく `body_ref` を出すため、抽出Event（[`event-extraction`](./event-extraction.md)）の `result` はnullになる（bodyが必要なら `body_ref` のfileを読む）。

## Do not

- `LoggerInterface` をdecorateしない — BEAR.ResourceにはResource実行を記録する `LoggerInterface` もあり紛らわしいが、観察bridgeの正しいdecorate対象は `InvokerInterface`。

## マスター確認（After）

- [ ] Resource実行後にSemantic Loggerログが生成され、Event抽出可能になることを `ResourceObservationTest.php` 相当で green。

## See also

- [`event-extraction`](./event-extraction.md) — 観察ログからimmutable Eventを抽出する（このbridgeの下流）
- [`event-filter-replay`](./event-filter-replay.md) — 抽出したEventをフィルタしてreplayする
- [`event-store-persistence`](./event-store-persistence.md) — 抽出したEventを永続化する
