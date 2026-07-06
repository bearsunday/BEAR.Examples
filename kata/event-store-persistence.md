# `event-store-persistence`

**EventStoreInterfaceでEventを永続化する** · [← 索引に戻る](../index.md)

- **Category:** Event Sourcing
- **Status:** `support`
- **Aliases:** EventStore, InMemoryEventStore, MediaQueryEventStore, event persistence, event storage, EventStoreQueryInterface, イベント永続化, イベントストア
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** 抽出したEventを永続化し、後から全Eventを再取得したい。

## 例

### EventStoreInterface

`append`, `appendAll`, `all` だけの小さい永続化ポート — runtime hookではない:

```php
interface EventStoreInterface
{
    public function append(Event $event): void;

    public function appendAll(EventsInterface $events): void;

    public function all(): EventsInterface;
}
```

### InMemoryEventStore（test用）

`appendAll` → `all` で同じEventが戻る:

```php
$store = new InMemoryEventStore();
$store->appendAll($this->exampleEvents());

$stored = $store->all();
$this->assertCount(1, $stored);
```

### MediaQueryEventStore（SQL永続化）

params/resultをJSONカラムにserializeし、timestampはマイクロ秒付きformatで保存する:

```php
private const TIMESTAMP_FORMAT = 'Y-m-d\TH:i:s.uP';

public function append(Event $event): void
{
    $this->query->append(
        uri: $event->uri,
        method: $event->method,
        paramsJson: self::encode($event->params),
        resultJson: self::encode($event->result),
        timestamp: $event->timestamp->format(self::TIMESTAMP_FORMAT),
    );
}
```

### EventStoreQueryInterface

Ray.MediaQuery経由の `#[DbQuery]`。SQLファイルと `event_store` テーブルはアプリ側で用意する:

```php
interface EventStoreQueryInterface
{
    #[DbQuery('event_store_append')]
    public function append(
        string $uri,
        string $method,
        string $paramsJson,
        string $resultJson,
        string $timestamp,
    ): AffectedRows;

    #[DbQuery('event_store_list')]
    public function list(): array;
}
```

## Naming

| 対象 | 命名 | 例 |
|---|---|---|
| Store実装 | `<Backing>EventStore` | `InMemoryEventStore`, `MediaQueryEventStore` |
| Query interface | `<Entity>QueryInterface` | `EventStoreQueryInterface` |
| SQLファイル | `<entity>_<verb>.sql` | `event_store_append.sql`, `event_store_list.sql` |

write は imperative verb（`append`）、一覧 read は `list()` — 通常のQuery/Command命名と同じ語彙。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `EventStoreInterface` は `append`, `appendAll`, `all` の小さい永続化ポートで、runtime hookではないと理解したか。
- [ ] test用は `InMemoryEventStore`、SQL永続化は `MediaQueryEventStore`（Ray.MediaQuery経由）を使うと決めたか。
- [ ] SQL永続化では `event_store_append` / `event_store_list` のSQLファイルとevent_storeテーブルをアプリ側で用意すると理解したか。

## Source

- [`vendor/bear/event-sourcing/src/EventStoreInterface.php`](../vendor/bear/event-sourcing/src/EventStoreInterface.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Store/InMemoryEventStore.php`](../vendor/bear/event-sourcing/src/Store/InMemoryEventStore.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Store/MediaQueryEventStore.php`](../vendor/bear/event-sourcing/src/Store/MediaQueryEventStore.php) *(external package)*
- [`vendor/bear/event-sourcing/src/Query/EventStoreQueryInterface.php`](../vendor/bear/event-sourcing/src/Query/EventStoreQueryInterface.php) *(external package)*

## Tests

- [`tests/Smoke/EventStoreTest.php`](../tests/Smoke/EventStoreTest.php)

## Key points

`EventStoreInterface` は小さい永続化ポート。InMemory（test）とMediaQuery（SQL）の2実装。ES ModuleはアプリのDB設定を隠さない（`MediaQueryEventStoreModule` は `EventStoreQueryInterface` の実装をアプリのMediaQueryModuleに委ねる）。`MediaQueryEventStore` はparams/resultをJSONカラムにserializeし、timestampはマイクロ秒付きで保存・復元する。

## Do not

- runtime中の自動永続化をしない — `EventStoreInterface` はruntime hookではなく明示的な永続化ポート。永続化するタイミングで `appendAll()` を呼ぶ。

## マスター確認（After）

- [ ] InMemoryEventStore に appendAll → all で同じEventが戻ることを `EventStoreTest.php` 相当で green。

## See also

- [`event-extraction`](./event-extraction.md) — 永続化するEventの抽出元（Semantic Logger → Event）
- [`event-filter-replay`](./event-filter-replay.md) — `all()` で取り出したEventsのフィルタとreplay
- [`resource-observation-bridge`](./resource-observation-bridge.md) — 観察ログの生成（Event抽出の入力）
- [`db-command-write`](./db-command-write.md) — Ray.MediaQueryでのwrite（`AffectedRows`）の基本形
- [`db-read-one-entity`](./db-read-one-entity.md) — `#[DbQuery]` + SQLファイルの基本形
