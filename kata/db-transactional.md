# `db-transactional`

**`#[Transactional]`で複数書き込みを原子化する** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** Transactional, transaction, rollback, TransactionalModule, トランザクション, 原子性
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** 複数のwrite（例: entity本体 + link table）を1トランザクションで原子化したい。
- **近いKata:** [`db-link-table-sync`](./db-link-table-sync.md)（clear→linkループはトランザクション化の典型候補）

## 例

このリポジトリに正規実装は無い（manual-only）。[公式マニュアル（database）](https://bearsunday.github.io/manuals/1.0/en/database.html) を一次資料として読み、原子化したいuse case境界のResource methodに `Ray\AuraSqlModule\Annotation\Transactional` を付ける。AOPがmethod実行をbegin/commitで包み、例外throwでrollbackする（backing classは `vendor/ray/aura-sql-module/src/TransactionalInterceptor.php`、このリポジトリにインストール済み）。複数接続DBは `#[Transactional(["pdo", "userDb"])]` のようにproperty指定する。典型候補は [`db-link-table-sync`](./db-link-table-sync.md) の clear→link ループ — entity本体のwriteと合わせて1トランザクションに包む。マスター確認は自プロジェクトに書いたテストのgreenが最終確証。

## Naming

この型に固有の命名は無い — attributeを付ける場所の規約が要点:

| 対象 | 規約 |
|---|---|
| `#[Transactional]` を付ける場所 | Resource method（`onPost` / `onPut` など use case境界） |
| 付けない場所 | `<Entity>QueryInterface` / `<Entity>CommandInterface` の method |
| 複数接続DB | `#[Transactional(["pdo", "userDb"])]` とproperty名で指定 |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] トランザクション境界をResource method（use case境界）に置き、Query/Command interfaceには持ち込まないと決めたか。
- [ ] `Ray\AuraSqlModule\Annotation\Transactional` をmethodに付けるとAOPでbegin/commit/rollbackされる（backing classは `vendor/ray/aura-sql-module/src/TransactionalInterceptor.php`、このリポジトリにインストール済み）と理解したか。
- [ ] 複数接続DBは `#[Transactional(["pdo", "userDb"])]` のようにproperty指定すると理解したか。

## Source

- [`vendor/ray/aura-sql-module/src/TransactionalInterceptor.php`](../vendor/ray/aura-sql-module/src/TransactionalInterceptor.php) *(external package)*

## Key points

例外throwでrollback。AuraSqlModule系のbindingにinterceptorが含まれる。

## Do not

- トランザクション内で外部API呼び出しなどrollback不能な副作用を起こさない。

## マスター確認（After）

- [ ] 途中失敗時に先行writeがrollbackされることを自プロジェクトのtestでpinしてgreen。

## See also

- [`db-link-table-sync`](./db-link-table-sync.md) — clear→linkループはトランザクション化の典型候補
- [`db-command-write`](./db-command-write.md) — 原子化する個々のwrite Commandの基本形
- [`api-post-input-dto`](./api-post-input-dto.md) — entity本体 + link tableを1 use caseで書くResource methodの実例
- [`error-status-mapping`](./error-status-mapping.md) — rollback時にthrowされた例外をHTTP statusへマップする
- [`mysql-integration-test`](./mysql-integration-test.md) — rollbackの検証は実DBで行う
