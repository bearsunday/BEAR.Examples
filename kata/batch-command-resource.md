# `batch-command-resource`

**バッチ/キューワーカーをCommand Resourceとして表現する** · [← 索引に戻る](../index.md)

- **Category:** External reference（外部参照実装の型）
- **Status:** `external`
- **Aliases:** command resource, batch, cron, queue worker, mail queue, scheduled job, バッチ, 定期実行, キューワーカー, メールキュー
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html （Resource一般）, https://bearsunday.github.io/manuals/1.0/en/cli.html （CLI起動）
- **Reference:** [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `src/Resource/Command/SendEmailFromEmailQueue.php`, `src/Resource/Command/DeleteAdmins.php`, `bin/command.php`
- **Use when:** cronやワーカーが実行するバッチ処理（メールキュー送信、掃除ジョブ、予約公開）をResourceの統一インターフェースで表現したい。
- **近いKata:** [`cli-resource`](./cli-resource.md)（HTTP以外からResourceを呼ぶ同型）, [`defer-resource-request`](./defer-resource-request.md)（応答後実行との使い分け — リトライが必要な処理はdeferでなくqueue+バッチ）

## 例

このリポジトリに参照実装は無い（external）。参照実装は [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) の `src/Resource/Command/SendEmailFromEmailQueue.php` / `src/Resource/Command/DeleteAdmins.php` / `bin/command.php` — ただしライセンス表記が無いため**コードはコピーせず**、構成・命名・責務分割という型だけを読み取って自プロジェクトで再実装する。型はこう: バッチ処理も通常の `ResourceObject` として `Resource/Command/*` に置き、実行を `onPost` で表す。cron側は `php bin/command.php post /send-email-from-email-queue` のようにURIで起動し（[公式マニュアル（cli）](https://bearsunday.github.io/manuals/1.0/en/cli.html) 参照）、バッチ専用のCLI context（bear-appでは `cli-command-app`）を用意する。処理の実体はUseCase/domain serviceに置き、Command Resourceは起動点（境界）のみに保つ。Resource設計の一次資料は [公式マニュアル（resource）](https://bearsunday.github.io/manuals/1.0/en/resource.html)。

## Naming

Command Resourceは「実行する動作」そのものをclass名にする:

| 対象 | 規約 | 例 |
|---|---|---|
| Command Resource class | `Resource/Command/` に命令形の動詞句 | `SendEmailFromEmailQueue`, `DeleteAdmins` |
| 実行method | `onPost`（実行＝POST） | — |
| 起動URI | class名のkebab-case | `post /send-email-from-email-queue` |
| CLI起動スクリプト | `bin/command.php` | `php bin/command.php post /send-email-from-email-queue` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] バッチも `ResourceObject`（`Resource/Command/*` の `onPost`）にし、cron側は `php bin/command.php post /send-email-from-email-queue` のようにURIで起動すると決めたか。
- [ ] バッチ専用のCLI context（bear-appは `cli-command-app`）を用意すると理解したか。
- [ ] 処理の実体はUseCase/domain serviceに置き、Command Resourceは境界（起動点）のみにすると決めたか。

## Source

- [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `src/Resource/Command/SendEmailFromEmailQueue.php`, `src/Resource/Command/DeleteAdmins.php`, `bin/command.php` *(external repo — コードはコピーしない)*

## Key points

ジョブの入口もURIになるため、手動再実行・テスト・監視が通常のResourceと同じ道具で揃う。DBのqueueテーブル＋定期起動のCommand Resourceという構成は、失敗時に再実行可能なジョブ（メール送信等）の置き場所として `#[Defer]`（応答後・at-most-once — [`defer-resource-request`](./defer-resource-request.md)）と補完関係にある。

## Do not

- リトライ必須の処理をdefer（応答後実行）で代用しない — `#[Defer]` はat-most-onceで、失敗したら再実行の機会が無い。queueテーブル＋定期起動のバッチ側に置く。

## マスター確認（After）

- [ ] CLIからCommand Resource経由でジョブが実行され、同じResourceをテストから `ResourceInterface` で呼べることを確認してgreen。

## See also

- [`cli-resource`](./cli-resource.md) — HTTP以外からResourceを呼ぶ同型（CLI起動の基本）
- [`defer-resource-request`](./defer-resource-request.md) — 応答後実行（at-most-once）との使い分け
- [`db-command-write`](./db-command-write.md) — ジョブの実体が呼ぶwrite Commandの基本形
- [`state-transition-resource`](./state-transition-resource.md) — 予約公開など状態遷移をResourceで表す
- [`app-resource-test`](./app-resource-test.md) — 同じResourceを `ResourceInterface` で呼んでテストする
