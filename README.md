# BEAR.Kata

BEAR.Sunday アプリケーションの実装パターン集。「これを実装したい時、どのファイルを見るか」をAIエージェントと人間が素早く引くためのリファレンス実装プロジェクト。

各Kataの詳細（Aliases / 着手前チェック / Source / Tests / Key points / マスター確認）は [docs/source-index.md](docs/source-index.md) を参照。AIエージェントは intent から `/bear-kata` スキル（[.claude/skills/bear-kata](.claude/skills/bear-kata/SKILL.md)）経由でも該当Kataを引ける。

## Kata 一覧

| Status | 意味 |
|---|---|
| `canonical` | 通常の実装で最初に真似する正規形 |
| `showcase` | 特定機能を切り出して見せる実例 |
| `comparison-only` | 比較理解用。デフォルト実装としてコピーしない |
| `support` | テスト、Fake、生成物など正規形を支える周辺実装 |

### Data access / BDR

| ID | 説明 | Status |
|---|---|---|
| [`db-read-one-entity`](docs/source-index.md#db-read-one-entity) | DBから主キーで1件のEntityを読む | canonical |
| [`db-read-by-natural-key`](docs/source-index.md#db-read-by-natural-key) | natural keyで1件読む | canonical |
| [`db-read-list-pager`](docs/source-index.md#db-read-list-pager) | DBから一覧をページングして読む | canonical |
| [`db-command-write`](docs/source-index.md#db-command-write) | DB書き込みをCommand Interfaceに分ける | canonical |
| [`db-link-table-sync`](docs/source-index.md#db-link-table-sync) | link tableをclear/linkで同期する | canonical |
| [`db-result-projection`](docs/source-index.md#db-result-projection) | Query結果を専用Result objectにする | showcase |
| [`db-array-row-comparison`](docs/source-index.md#db-array-row-comparison) | Entityではなくarrayで読む比較例を見る | comparison-only |
| [`db-sqlquery-orchestration`](docs/source-index.md#db-sqlquery-orchestration) | `SqlQueryInterface`で複数SQLを調停する | comparison-only |
| [`db-raw-pdo-comparison`](docs/source-index.md#db-raw-pdo-comparison) | Raw PDOとの違いを見る | comparison-only |

### Resource / API

| ID | 説明 | Status |
|---|---|---|
| [`api-get-hal-resource`](docs/source-index.md#api-get-hal-resource) | GET ResourceをHAL+JSONで返す | canonical |
| [`api-post-input-dto`](docs/source-index.md#api-post-input-dto) | POST入力をInput DTOで受ける | canonical |
| [`api-put-tristate-input`](docs/source-index.md#api-put-tristate-input) | PUTでtri-state入力を扱う | canonical |
| [`api-delete-no-content`](docs/source-index.md#api-delete-no-content) | DELETE成功を204で返す | canonical |
| [`not-found-response`](docs/source-index.md#not-found-response) | 見つからないResourceを404にする | canonical |
| [`json-schema-validation`](docs/source-index.md#json-schema-validation) | Request/ResponseをJSON Schemaで検証する | canonical |
| [`hal-link`](docs/source-index.md#hal-link) | HAL `_links` を `#[Link]` で宣言する | canonical |
| [`hal-embed`](docs/source-index.md#hal-embed) | HAL `_embedded` を `#[Embed]` と `addQuery()` で作る | canonical |

### HTML / Page

| ID | 説明 | Status |
|---|---|---|
| [`page-resource-qiq-detail`](docs/source-index.md#page-resource-qiq-detail) | Page Resourceで1件詳細HTMLを描画する | canonical |
| [`page-resource-list`](docs/source-index.md#page-resource-list) | Page Resourceで一覧HTMLを描画する | canonical |
| [`markdown-to-html`](docs/source-index.md#markdown-to-html) | Markdown本文をHTMLへ変換する | canonical |
| [`admin-prg-form`](docs/source-index.md#admin-prg-form) | Admin formでPRGを使う | showcase |

### Runtime / representation

| ID | 説明 | Status |
|---|---|---|
| [`stream-response`](docs/source-index.md#stream-response) | ファイルやバイナリをストリームで返す | showcase |
| [`cacheable-leaf`](docs/source-index.md#cacheable-leaf) | `#[Cacheable]` だけのleaf resourceを作る | showcase |
| [`cache-embed-dependency`](docs/source-index.md#cache-embed-dependency) | `#[Embed]` 親Resourceの依存を自動合成する | showcase |
| [`cache-body-derived-dependency`](docs/source-index.md#cache-body-derived-dependency) | body由来の可変長依存を `fromAssoc()` で宣言する | showcase |
| [`async-embed-parallel`](docs/source-index.md#async-embed-parallel) | embed graphを並列実行に載せる | showcase |
| [`cli-resource`](docs/source-index.md#cli-resource) | ResourceをCLIコマンドとして公開する | showcase |

### Tests / fake

| ID | 説明 | Status |
|---|---|---|
| [`fake-sql-query`](docs/source-index.md#fake-sql-query) | DBなしでMediaQueryをFakeする | support |
| [`app-resource-test`](docs/source-index.md#app-resource-test) | App ResourceのAPI contractをテストする | support |
| [`page-resource-test`](docs/source-index.md#page-resource-test) | Page ResourceのHTML contractをテストする | support |
| [`hypermedia-workflow-test`](docs/source-index.md#hypermedia-workflow-test) | Link/Embedを辿るworkflowをテストする | support |
| [`mysql-integration-test`](docs/source-index.md#mysql-integration-test) | 実DB経路を必要時だけ検証する | support |

### Semantic / generated artifacts

| ID | 説明 | Status |
|---|---|---|
| [`alps-profile-ssot`](docs/source-index.md#alps-profile-ssot) | ALPS profileを意味のSSOTにする | support |
| [`semantic-fake-data`](docs/source-index.md#semantic-fake-data) | semantic-exで決定的fake dataを作る | support |
| [`json-schema-generated`](docs/source-index.md#json-schema-generated) | fake observationからJSON Schemaを生成する | support |
| [`apidoc-llms-generated`](docs/source-index.md#apidoc-llms-generated) | API docsとllms.txtを生成する | support |

## クイックスタート

```bash
composer install && composer fake
php -r 'require "autoload.php"; $r = BEAR\Kata\Injector::getInstance("fake-hal-api-app")->getInstance(BEAR\Resource\ResourceInterface::class); echo json_encode($r->get("app://self/article", ["id" => 1])->body, JSON_PRETTY_PRINT);'
composer test
```

## ドキュメント

- [docs/source-index.md](docs/source-index.md) — 各KataのSource / Tests / Key points 詳細
- [docs/setup.md](docs/setup.md) — DB・サーバー起動・管理者ログイン
- [docs/architecture.md](docs/architecture.md) — BDRレイアウトと設計根拠
- [docs/conventions.md](docs/conventions.md) — 命名・Resourceパターン・Read/Write SQL契約
- [docs/scope.md](docs/scope.md) — 実装範囲と意図的な除外事項

## Links

- [BEAR.Sunday manual](https://bearsunday.github.io/manuals/1.0/en/index.html)
- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery)
- [ALPS](https://alps.io/)
