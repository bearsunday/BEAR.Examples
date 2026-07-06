# `event-extraction`

**Semantic Logger観察ログからimmutable Eventを抽出する** · [← 索引に戻る](../index.md)

- **Category:** Event Sourcing
- **Status:** `showcase`
- **Aliases:** event sourcing, SemanticLogExtractor, Event, RecordedMethods, semantic logger, event extraction, observation, イベントソーシング, イベント抽出, 観察ログ
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** アプリケーションの状態変化をイベントとして記録し、replay可能なsource of truthにしたい。

## 例

### Event

resource操作（method on uri）の不変事実のみを持つ:

```php
final readonly class Event
{
    public string $method;

    public function __construct(
        public string $uri,
        string $method,
        public DateTimeImmutable $timestamp,
        public array $params = [],
        public mixed $result = null,
    ) {
        $this->method = strtoupper($method);
    }
}
```

### RecordedMethods

記録対象methodの制御。`new RecordedMethods()` のデフォルトは `STATE_CHANGING`（write系のみ、GETは除外）:

```php
public const STATE_CHANGING = ['POST', 'PUT', 'PATCH', 'DELETE'];

public const WITH_READS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];
```

### SemanticLogExtractor

open/close観察ツリーをwalkし、open contextの `uri`/`method`/`params`/`timestamp` とclose contextの `body` からEventを組む。記録対象外のmethodはスキップ:

```php
private function appendEvent(array $entry, array &$events): void
{
    $request = self::context($entry);
    $response = self::closeContext($entry);
    if ($request === null || $response === null || ! self::isSuccessful($response)) {
        return;
    }

    $method = $this->recordedMethod($request);
    $uri = self::stringValue($request, 'uri');
    if ($method === null || $uri === null) {
        return;
    }

    $events[] = new Event(
        uri: $uri,
        method: $method,
        timestamp: self::timestamp($request) ?? new DateTimeImmutable(),
        params: self::params($request),
        result: $response['body'] ?? null,
    );
}
```

失敗した操作（code >= 400）もスキップする:

```php
private static function isSuccessful(array $context): bool
{
    $code = $context['code'] ?? null;

    return ! is_int($code) || $code < 400;
}
```

### 観察→抽出

Semantic Loggerのopen/closeを観察context（request側: `uri`/`method`/`query`/`timestamp`、response側: `code`/`body`）で積み、`flush()` した `LogJson` から抽出する:

```php
$logger = new SemanticLogger();

$post = $logger->open(new FakeResourceRequestContext(
    uri: 'app://self/articles',
    method: 'POST',
    query: ['title' => 'Hello'],
    timestamp: '2026-06-10T12:34:56.123456+00:00',
));
$logger->close(new FakeResourceResponseContext(201, ['id' => 1]), $post);

$events = (new SemanticLogExtractor())->extract($logger->flush());
```

GETも記録するなら `WITH_READS` を渡す:

```php
$events = (new SemanticLogExtractor(
    new RecordedMethods(RecordedMethods::WITH_READS),
))->extract($logger->flush());
```

### Module

DIで使う場合は `EventSourcingModule` が extractor をbindする（記録範囲・storeは任意で差し込む）:

```php
protected function configure(): void
{
    if ($this->methods !== null) {
        $this->bind(RecordedMethods::class)->toInstance($this->methods);
    }

    $this->bind(SemanticLogExtractorInterface::class)->to(SemanticLogExtractor::class);

    if ($this->store !== null) {
        $this->install($this->store);
    }
}
```

## Naming

Event Sourcing まわりの命名（BEAR.EventSourcing パッケージ準拠）:

| 種別 | 形 | 例 |
|---|---|---|
| Event | 事実のみを持つ `final readonly class` | `Event`（`uri`, `method`, `params`, `timestamp`, `result`） |
| 記録範囲 | `RecordedMethods` + 定数 | `STATE_CHANGING` / `WITH_READS` |
| 抽出器 | `<Source>Extractor` + interface | `SemanticLogExtractor` / `SemanticLogExtractorInterface` |
| 観察context | `<層><方向>Context`、`TYPE` 定数はsnake_case | `resource_request` / `resource_response` |
| テスト代替 | `Fake<Name>`（`tests/Fake/`） | `FakeResourceRequestContext` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] Semantic Loggerのopen/close観察ツリーからEventを抽出し、ドメインにevent-dispatchコードを追加しないと理解したか。
- [ ] `RecordedMethods` で記録対象method（デフォルト: POST/PUT/PATCH/DELETE、GETは除外）を制御すると決めたか。
- [ ] Eventは `uri`, `method`, `params`, `timestamp`, `result` の事実のみを持つと理解したか。

## Source

- [`vendor/bear/event-sourcing/src/SemanticLogExtractor.php`](../vendor/bear/event-sourcing/src/SemanticLogExtractor.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Event.php`](../vendor/bear/event-sourcing/src/Event.php) *(external package)*
- [`vendor/bear/event-sourcing/src/RecordedMethods.php`](../vendor/bear/event-sourcing/src/RecordedMethods.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Module/EventSourcingModule.php`](../vendor/bear/event-sourcing/src/Module/EventSourcingModule.php) *(external package)*
- [`tests/Fake/FakeResourceRequestContext.php`](../tests/Fake/FakeResourceRequestContext.php)
- [`tests/Fake/FakeResourceResponseContext.php`](../tests/Fake/FakeResourceResponseContext.php)

## Tests

- [`tests/Smoke/EventExtractionTest.php`](../tests/Smoke/EventExtractionTest.php)

## Key points

Semantic Loggerが観察源。Eventはresource操作（method on uri）の不変事実。`RecordedMethods` で記録範囲を制御。code >= 400 はスキップ。Eventの `result` はclose contextの `body` フィールドから取る — [`resource-observation-bridge`](./resource-observation-bridge.md) の実contextは `body_ref` を出すため、bridge由来logから抽出したEventの `result` は null になる点に注意。

## Do not

- ドメインコードにevent-dispatchを追加しない — 他フレームワークのevent sourcingではドメイン側でイベントを発行するのが常道だが、この型ではSemantic Loggerの観察ツリーが唯一のイベント源。発行コードを書いた時点で観察と発行の二重管理になる。

## マスター確認（After）

- [ ] write操作（POST/PUT/PATCH/DELETE）のみがデフォルトで抽出される。
- [ ] `RecordedMethods::WITH_READS` でGETも含まれる。
- [ ] code >= 400 の操作はスキップされることを `EventExtractionTest.php` 相当で green。

## See also

- [`resource-observation-bridge`](./resource-observation-bridge.md) — 実Resource実行から観察ログ（この型の入力）を生成する
- [`event-filter-replay`](./event-filter-replay.md) — 抽出したEventsをフィルタしてreplayする
- [`event-store-persistence`](./event-store-persistence.md) — 抽出したEventをEventStoreで永続化する
