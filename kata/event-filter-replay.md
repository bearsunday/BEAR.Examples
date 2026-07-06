# `event-filter-replay`

**Eventsコレクションをフィルタしてreplayする** · [← 索引に戻る](../index.md)

- **Category:** Event Sourcing
- **Status:** `showcase`
- **Aliases:** event replay, filter events, CallbackFilterIterator, Events, replay, projection, イベントリプレイ, 再生, イベントフィルタ
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** 抽出したEventをURI prefix / params / timestampでフィルタし、特定エンティティの状態変化をreplayしたい。

## 例

### Event / EventsInterface

Eventは事実のみを持つimmutableなrecord（→ [`event-extraction`](./event-extraction.md)）:

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

`EventsInterface` はcountable + iterable — 専用のquery methodは持たない:

```php
interface EventsInterface extends Countable, IteratorAggregate
{
}
```

### 単一条件でフィルタ

`IteratorAggregate` なので `getIterator()` を明示的に渡す。filterはkeyを保持するため `iterator_to_array($it, false)` でlist化する:

```php
$events = (new SemanticLogExtractor())->extract($logger->flush());

$forUser = new CallbackFilterIterator(
    $events->getIterator(),
    static fn ($e): bool => ($e->params['user_id'] ?? null) === 'koriym',
);

$filtered = iterator_to_array($forUser, false);
```

URI prefixも同じ形（timestampも `$e->timestamp` の比較で同手法）:

```php
$orderEvents = new CallbackFilterIterator(
    $events->getIterator(),
    static fn ($e): bool => str_starts_with($e->uri, 'app://self/orders'),
);
```

### フィルタをstackする

filterの出力を次のfilterの入力にする — Eventsにmethodを生やさない:

```php
$forUser = new CallbackFilterIterator(
    $events->getIterator(),
    static fn ($e): bool => ($e->params['user_id'] ?? null) === 'koriym',
);
$writesOnly = new CallbackFilterIterator(
    $forUser,
    static fn ($e): bool => $e->method === 'POST',
);

$filtered = iterator_to_array($writesOnly, false);
```

## Naming

この型でclass・interface・SQLファイルは増えない。Eventのプロパティ名（`uri`, `method`, `params`, `timestamp`, `result`）はパッケージ側で固定。filter変数には抽出意図を表す名前を付ける — `$forUser` / `$orderEvents` / `$writesOnly`。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `Events` はcountable + iterableで、PHP標準の `CallbackFilterIterator` でフィルタすると理解したか。
- [ ] query methodをEventsに追加せず、filterをstackすると理解したか。

## Source

- [`vendor/bear/event-sourcing/src/Events.php`](../vendor/bear/event-sourcing/src/Events.php) *(external package)*
- [`vendor/bear/event-sourcing/src/EventsInterface.php`](../vendor/bear/event-sourcing/src/EventsInterface.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Event.php`](../vendor/bear/event-sourcing/src/Event.php) *(external package)*

## Tests

- [`tests/Smoke/EventReplayTest.php`](../tests/Smoke/EventReplayTest.php)

## Key points

`CallbackFilterIterator` でURI prefix / params / method（timestampも `Event->timestamp` の比較で同手法）をstacked filterする。query methodを生やさずfilterをstack。`EventsInterface` は `IteratorAggregate` なので `getIterator()` を明示的に渡し、filterはkeyを保持するため `iterator_to_array($it, false)` でlist化する。

## Do not

- Eventsコレクションに専用query methodを追加しない（PHP標準iteratorで十分）。

## マスター確認（After）

- [ ] 特定id / URI prefix / method でフィルタされたEventが正しく抽出されることを `EventReplayTest.php` 相当で green。

## See also

- [`event-extraction`](./event-extraction.md) — replayの入力となるEventをSemantic Loggerログから抽出する（前段）
- [`event-store-persistence`](./event-store-persistence.md) — Eventを永続化し、後から全件再取得する
- [`resource-observation-bridge`](./resource-observation-bridge.md) — BEAR.Resource実行から観察ログを生成する（Event抽出の入力源）
