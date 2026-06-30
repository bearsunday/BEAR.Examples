# BEAR.Kata

BEAR.Sunday アプリケーションの実装パターン集。「これを実装したい時、どのファイルを見るか」をAIエージェントと人間が素早く引くためのリファレンス実装プロジェクト。

各Kataの詳細（Aliases / 着手前チェック / Source / Tests / Key points / マスター確認）は [docs/source-index.md](docs/source-index.md) を参照。AIエージェントは intent から `/bear-kata` スキル（[.claude/skills/bear-kata](.claude/skills/bear-kata/SKILL.md)）経由でも該当Kataを引ける。

## カバレッジ

BEAR.Sunday公式ドキュメントの主要機能と、この索引の対応状況。詳細は [docs/scope.md](docs/scope.md) を参照。

| 機能領域 | Kata有無 | 該当Kata |
|---|---|---|
| Resource GET / POST / PUT / DELETE | ✅ | `api-get-hal-resource`, `api-post-input-dto`, `api-put-tristate-input`, `api-delete-no-content` |
| Resource PATCH | ❌ 未実装 | `onPatch` はBEAR.Sundayがネイティブ対応。tri-state入力の型は `api-put-tristate-input` を流用可能 |
| Resource OPTIONS | ❌ 未実装 | `OptionsMethodModule` が公式に存在 |
| HAL `_links` / `_embedded` | ✅ | `hal-link`, `hal-embed` |
| Crawl / DataLoader | ✅ | `crawl-data-loader` |
| Not Found (404) | ✅ | `not-found-response` |
| JSON Schema validation | ✅ | `json-schema-validation`, `json-schema-generated` |
| DB: BDR read / write / pager / link table / after-insert lookup | ✅ | `db-read-one-entity`, `db-read-by-natural-key`, `db-read-list-pager`, `db-command-write`, `db-link-table-sync` |
| DB: Result projection / CQRS | ✅ | `db-result-projection` |
| Cache: `#[Cacheable]` / `#[DonutCache]` / `#[CacheableResponse]` / `#[Purge]` | ✅ | `cacheable-leaf`, `donut-cache`, `cacheable-response`, `cache-purge` |
| Cache: `#[Embed]` dependency / `fromAssoc` | ✅ | `cache-embed-dependency`, `cache-body-derived-dependency` |
| Cache: ETag 配信 | ✅ | `cacheable-response` がETagを付与 |
| Cache: 304 Conditional Request（If-None-Match） | ❌ 未実装 | ETag配信は `cacheable-response` が対応、304応答は未実装 |
| HTML: Page / Qiq detail / list | ✅ | `page-resource-qiq-detail`, `page-resource-list` |
| HTML: Markdown 変換 | ✅ | `markdown-to-html` |
| HTML: Admin PRG / auth boundary | ✅ | `admin-prg-form`, `admin-auth-boundary` |
| Stream response | ✅ | `stream-response` |
| Async / Parallel embed | ✅ | `async-embed-parallel` |
| CLI | ✅ | `cli-resource` |
| OAuth 認証 | ✅ | `auth-oauth-flow` |
| CSRF / Same-Origin 保護 | ✅ | `csrf-same-origin-protection` |
| ファイルアップロード | ✅ | `file-upload-input` |
| 状態遷移 Resource | ✅ | `state-transition-resource` |
| エラーハンドリング | ✅ | `error-status-mapping` |
| Event Sourcing | ✅ | `event-extraction`, `event-filter-replay`, `event-store-persistence`, `resource-observation-bridge` |
| Deferred execution | ✅ | `defer-resource-request`, `defer-conditional` |
| Import (cross-app) | ✅ | `import-app` |
| ALPS / API Doc / fake data | ✅ | `alps-profile-ssot`, `apidoc-llms-generated`, `semantic-fake-data` |
| Content Negotiation | ❌ 未実装 | `BEAR.Accept`, `#[Produces]` が公式に存在 |
| Ray.WebFormModule フォームバリデーション | ❌ 未実装 | `admin-prg-form` はPRGのみ |
| Production デプロイ / compile / preload | ❌ スコープ外 | インフラ層 |
| High-Performance Servers (Swoole / RR / FrankenPHP) | ❌ スコープ外 | ランタイム層 |
| Aura.Router カスタムルーティング | ❌ スコープ外 | フレームワーク設定 |

## Kata 一覧

> 各Kataの `Aliases`（検索用キーワード）は [docs/source-index.md](docs/source-index.md) に記載されています。より具体的なintent語（`after insert lookup`, `double submit cookie`, `linkCrawl` 等）で検索する場合は source-index.md を引いてください。

| Status | 意味 |
|---|---|
| `canonical` | 通常の実装で最初に真似する正規形 |
| `showcase` | 特定機能を切り出して見せる実例 |
| `comparison-only` | 比較理解用。デフォルト実装としてコピーしない |
| `support` | テスト、Fake、生成物など正規形を支える周辺実装 |

### Data access / BDR
DB読み書きの正規形。Ray.MediaQuery + `#[DbQuery]` + Entity/Factory のBDRパターン。

| ID | 説明 | Status |
|---|---|---|
| [`db-read-one-entity`](docs/source-index.md#db-read-one-entity) | DBから主キーで1件のEntityを読む | canonical |
| [`db-read-by-natural-key`](docs/source-index.md#db-read-by-natural-key) | natural keyで1件読む（INSERT後の新規ID回収） | canonical |
| [`db-read-list-pager`](docs/source-index.md#db-read-list-pager) | DBから一覧をページングして読む | canonical |
| [`db-command-write`](docs/source-index.md#db-command-write) | DB書き込みをCommand Interfaceに分ける | canonical |
| [`db-link-table-sync`](docs/source-index.md#db-link-table-sync) | link tableをclear/linkで同期する | canonical |
| [`db-result-projection`](docs/source-index.md#db-result-projection) | Query結果を専用Result objectにする | showcase |
| [`db-array-row-comparison`](docs/source-index.md#db-array-row-comparison) | Entityではなくarrayで読む比較例を見る | comparison-only |
| [`db-sqlquery-orchestration`](docs/source-index.md#db-sqlquery-orchestration) | `SqlQueryInterface`で複数SQLを調停する | comparison-only |
| [`db-raw-pdo-comparison`](docs/source-index.md#db-raw-pdo-comparison) | Raw PDOとの違いを見る | comparison-only |

### Resource / API
App ResourceのHTTP メソッド別パターン。HAL+JSON、入力DTO、バリデーション、エラー、OAuth、ファイルアップロード、Crawl/DataLoader。

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
| [`auth-oauth-flow`](docs/source-index.md#auth-oauth-flow) | OAuth認証フローをAuthInterface経由で示す | showcase |
| [`file-upload-input`](docs/source-index.md#file-upload-input) | `#[InputFile]`でファイルアップロードを受ける | canonical |
| [`crawl-data-loader`](docs/source-index.md#crawl-data-loader) | `#[Link(crawl:...)]` + DataLoaderでN+1を解消する | showcase |
| [`state-transition-resource`](docs/source-index.md#state-transition-resource) | 状態遷移を独立Resourceとして切り出す | canonical |
| [`error-status-mapping`](docs/source-index.md#error-status-mapping) | 例外→HTTPステータスマッピングとエラーハンドリング | canonical |

### HTML / Page
Page Resource + Qiq template によるHTML描画。Markdown変換、Admin PRG、認可境界。

| ID | 説明 | Status |
|---|---|---|
| [`page-resource-qiq-detail`](docs/source-index.md#page-resource-qiq-detail) | Page Resourceで1件詳細HTMLを描画する | canonical |
| [`page-resource-list`](docs/source-index.md#page-resource-list) | Page Resourceで一覧HTMLを描画する（pagerは `db-read-list-pager` を使用） | canonical |
| [`markdown-to-html`](docs/source-index.md#markdown-to-html) | Markdown本文をHTMLへ変換する | canonical |
| [`admin-prg-form`](docs/source-index.md#admin-prg-form) | Admin formでPRGを使う | showcase |
| [`admin-auth-boundary`](docs/source-index.md#admin-auth-boundary) | AdminGuardによるauthor-scoped認可境界 | showcase |

### Runtime / representation
キャッシュ（`#[Cacheable]` / `#[DonutCache]` / `#[CacheableResponse]` / `#[Purge]`）、ストリーム、Async/Parallel、CLI、CSRF保護、Import App。

| ID | 説明 | Status |
|---|---|---|
| [`stream-response`](docs/source-index.md#stream-response) | ファイルやバイナリをストリームで返す | showcase |
| [`cacheable-leaf`](docs/source-index.md#cacheable-leaf) | `#[Cacheable]` だけのleaf resourceを作る | showcase |
| [`cache-embed-dependency`](docs/source-index.md#cache-embed-dependency) | `#[Embed]` 親Resourceの依存を自動合成する | showcase |
| [`cache-body-derived-dependency`](docs/source-index.md#cache-body-derived-dependency) | `fromAssoc()` + Surrogate-Keyでbody由来の可変長依存を宣言する | showcase |
| [`async-embed-parallel`](docs/source-index.md#async-embed-parallel) | embed graphを並列実行に載せる | showcase |
| [`cli-resource`](docs/source-index.md#cli-resource) | ResourceをCLIコマンドとして公開する | showcase |
| [`csrf-same-origin-protection`](docs/source-index.md#csrf-same-origin-protection) | CSRFトークン + Same-Origin interceptorをAOP bindする | canonical |
| [`cache-purge`](docs/source-index.md#cache-purge) | `#[Purge]`でwrite時にcollection cacheを手動無効化する | showcase |
| [`donut-cache`](docs/source-index.md#donut-cache) | `#[DonutCache]`でドーナツキャッシュ（Donut Cache / Donut Hole）を示す | showcase |
| [`cacheable-response`](docs/source-index.md#cacheable-response) | `#[CacheableResponse]`でレスポンス全体キャッシュ + ETag配信 | showcase |
| [`import-app`](docs/source-index.md#import-app) | ImportAppModuleで他アプリのResourceを呼ぶ | showcase |

### Event Sourcing
Semantic Logger観察 → Event抽出（`event-extraction` がentry）→ フィルタ/replay → 永続化。Resource実行の観察ブリッジ。

| ID | 説明 | Status |
|---|---|---|
| [`event-extraction`](docs/source-index.md#event-extraction) | Semantic Logger観察ログからEventを抽出する | showcase |
| [`event-filter-replay`](docs/source-index.md#event-filter-replay) | Eventsをフィルタしてreplayする | showcase |
| [`event-store-persistence`](docs/source-index.md#event-store-persistence) | EventStoreInterfaceでEventを永続化する | support |
| [`resource-observation-bridge`](docs/source-index.md#resource-observation-bridge) | BEAR.Resource実行から観察ログを生成する | showcase |

### Deferred execution
`#[Defer]` + `#[Link]` で応答後にfollow-up Resourceを実行。202 Accepted即時返却。

| ID | 説明 | Status |
|---|---|---|
| [`defer-resource-request`](docs/source-index.md#defer-resource-request) | `#[Defer]` + `#[Link]`で応答後にfollow-upを実行する | showcase |
| [`defer-conditional`](docs/source-index.md#defer-conditional) | `DeferInterface::add()`で条件付きdeferを手動制御する | showcase |

### Tests / fake
DBなしテストのFake基盤。Resource/Page/Hypermedia contract test、MySQL integration。

| ID | 説明 | Status |
|---|---|---|
| [`fake-sql-query`](docs/source-index.md#fake-sql-query) | DBなしでMediaQueryをFakeする | support |
| [`app-resource-test`](docs/source-index.md#app-resource-test) | App ResourceのAPI contractをテストする | support |
| [`page-resource-test`](docs/source-index.md#page-resource-test) | Page ResourceのHTML contractをテストする | support |
| [`hypermedia-workflow-test`](docs/source-index.md#hypermedia-workflow-test) | Link/Embedを辿るworkflowをテストする | support |
| [`mysql-integration-test`](docs/source-index.md#mysql-integration-test) | 実DB経路を必要時だけ検証する | support |

### Semantic / generated artifacts
ALPS profile SSOT、決定的fake data、JSON Schema生成、API doc + llms.txt生成。

| ID | 説明 | Status |
|---|---|---|
| [`alps-profile-ssot`](docs/source-index.md#alps-profile-ssot) | ALPS profileを意味のSSOTにする | support |
| [`semantic-fake-data`](docs/source-index.md#semantic-fake-data) | semantic-exで決定的fake dataを作る | support |
| [`json-schema-generated`](docs/source-index.md#json-schema-generated) | fake observationからJSON Schemaを生成する | support |
| [`apidoc-llms-generated`](docs/source-index.md#apidoc-llms-generated) | API docsとllms.txtを生成する | support |

## スキルを獲得して使う

このリポジトリの価値は CMS を動かすことではなく、`bear-kata` スキルを獲得して **自分の BEAR.Sunday 実装に型(Kata)を適用する** ことにあります。

### 1. スキルを獲得する

```bash
# 個人用（どのプロジェクトでも有効）
mkdir -p ~/.claude/skills && cp -r .claude/skills/bear-kata ~/.claude/skills/

# または特定プロジェクト用
mkdir -p /path/to/your-project/.claude/skills && cp -r .claude/skills/bear-kata /path/to/your-project/.claude/skills/
```

このリポジトリ内で Claude Code を開く場合は、`.claude/skills/bear-kata/` が自動で有効になるのでコピー不要です。

### 2. スキルを発動する

Claude Code で、実装したいことを伝えるだけです。

> 記事一覧のページングを **kata に従って実装してください**

「kata に従って」「BEAR.Sunday で〜を実装したい」と言うと `bear-kata` スキルが発動し、[ソース索引](docs/source-index.md) から該当 Kata（着手前チェック → Source / Tests → マスター確認）へ誘導します。`/bear-kata` で明示的に呼ぶこともできます。

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
