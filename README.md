# BEAR.Kata

BEAR.Sunday アプリケーションの実装パターン集（Kata = 型）。「これを実装したい時、どのファイルを見るか」をAIエージェントと人間が素早く引くためのリファレンス実装プロジェクト。

## 引き方

1. **下の[索引](#索引)を「やりたいこと」で探す。** 各Kataの詳細（Aliases / 着手前チェック / Source / Tests / Key points / マスター確認）はリンク先の [docs/source-index.md](docs/source-index.md) にある。
2. **キーワードで引く。** `pager` / `#[Embed]` / `PRG` / `多対多` / `認可` / `条件付きリクエスト` などの検索語（Aliases）は source-index.md の各エントリに載っている。この索引で見つからなければ source-index.md を全文検索する。
3. **AIに引かせる。** Claude Code で「◯◯を kata に従って実装して」と言うと [`bear-kata` スキル](#スキルを獲得して使う)が該当Kataへ誘導する。

| Status | 意味 |
|---|---|
| `canonical` | 最初に真似する正規形 |
| `showcase` | 特定機能を切り出した実例 |
| `comparison-only` | 比較理解用。コピーしない |
| `support` | テスト・Fake・生成物 |
| `manual-only` | 型の記述のみ。一次資料は公式マニュアル |
| `external` | 型の記述のみ。一次資料は外部の参照実装（コードはコピーしない） |

## 索引

### DBを読み書きする

| やりたいこと | Kata | Status |
|---|---|---|
| 主キーで1件のEntityを読む | [`db-read-one-entity`](docs/source-index.md#db-read-one-entity) | canonical |
| DB行をEntityへ変換する（enum・日付正規化） | [`db-entity-factory`](docs/source-index.md#db-entity-factory) | canonical |
| natural keyで1件読む（INSERT後の新規ID回収） | [`db-read-by-natural-key`](docs/source-index.md#db-read-by-natural-key) | canonical |
| 一覧をページング・絞り込みして読む | [`db-read-list-pager`](docs/source-index.md#db-read-list-pager) | canonical |
| 作成・更新・削除をCommandに分ける | [`db-command-write`](docs/source-index.md#db-command-write) | canonical |
| 多対多のlink tableを同期する | [`db-link-table-sync`](docs/source-index.md#db-link-table-sync) | canonical |
| SELECT結果を型付きコレクションで返す | [`db-result-projection`](docs/source-index.md#db-result-projection) | showcase |
| 複数書き込みをトランザクションで原子化する | [`db-transactional`](docs/source-index.md#db-transactional) | manual-only |
| Entityを使わないarray実装と比較する | [`db-array-row-comparison`](docs/source-index.md#db-array-row-comparison) | comparison-only |
| 複数SQLを`SqlQueryInterface`で調停する | [`db-sqlquery-orchestration`](docs/source-index.md#db-sqlquery-orchestration) | comparison-only |
| Raw PDOとの責務差を見る | [`db-raw-pdo-comparison`](docs/source-index.md#db-raw-pdo-comparison) | comparison-only |

### APIを作る（App Resource）

| やりたいこと | Kata | Status |
|---|---|---|
| GETをHAL+JSONで返す | [`api-get-hal-resource`](docs/source-index.md#api-get-hal-resource) | canonical |
| POST入力をInput DTOで受ける（201 + Location） | [`api-post-input-dto`](docs/source-index.md#api-post-input-dto) | canonical |
| PUTで「省略/空/指定」のtri-state入力を扱う | [`api-put-tristate-input`](docs/source-index.md#api-put-tristate-input) | canonical |
| PATCHで差分更新を受ける | [`api-patch-partial-update`](docs/source-index.md#api-patch-partial-update) | manual-only |
| DELETE成功を204で返す | [`api-delete-no-content`](docs/source-index.md#api-delete-no-content) | canonical |
| OPTIONSでメソッドとパラメータ仕様を返す | [`api-options-method`](docs/source-index.md#api-options-method) | manual-only |
| 見つからないResourceを404にする | [`not-found-response`](docs/source-index.md#not-found-response) | canonical |
| 入出力をJSON Schemaで検証する | [`json-schema-validation`](docs/source-index.md#json-schema-validation) | canonical |
| 状態遷移（draft→published）を独立Resourceにする | [`state-transition-resource`](docs/source-index.md#state-transition-resource) | canonical |
| 例外をHTTPステータスへマッピングする | [`error-status-mapping`](docs/source-index.md#error-status-mapping) | canonical |
| ファイルアップロードを受ける（`#[InputFile]`） | [`file-upload-input`](docs/source-index.md#file-upload-input) | canonical |
| cookie/env/他Resource値を引数に束縛する | [`web-context-param-binding`](docs/source-index.md#web-context-param-binding) | manual-only |
| AcceptヘッダでJSON/HTML/CSV等を出し分ける | [`content-negotiation`](docs/source-index.md#content-negotiation) | manual-only |
| 検証ロジックをAOPで分離する（`#[Valid]`） | [`aop-validation-valid`](docs/source-index.md#aop-validation-valid) | manual-only |

### リソースを繋ぐ（Hypermedia）

| やりたいこと | Kata | Status |
|---|---|---|
| 遷移先を `_links` で宣言する（`#[Link]`） | [`hal-link`](docs/source-index.md#hal-link) | canonical |
| 関連Resourceを `_embedded` に埋め込む（`#[Embed]`） | [`hal-embed`](docs/source-index.md#hal-embed) | canonical |
| リソースグラフのN+1をDataLoaderで解消する | [`crawl-data-loader`](docs/source-index.md#crawl-data-loader) | showcase |

### HTMLページを作る（Page Resource + Qiq）

| やりたいこと | Kata | Status |
|---|---|---|
| 1件詳細ページを描画する | [`page-resource-qiq-detail`](docs/source-index.md#page-resource-qiq-detail) | canonical |
| 一覧ページを描画する（pager・filter付き） | [`page-resource-list`](docs/source-index.md#page-resource-list) | canonical |
| Markdown本文をHTMLへ変換する | [`markdown-to-html`](docs/source-index.md#markdown-to-html) | canonical |
| 管理フォームでPRG（成功時303 / 失敗時422再描画） | [`admin-prg-form`](docs/source-index.md#admin-prg-form) | showcase |
| 確認画面を挟んで状態遷移を実行する | [`admin-confirm-page`](docs/source-index.md#admin-confirm-page) | showcase |
| form classにフィールド定義と検証を集約する | [`form-validation-webform`](docs/source-index.md#form-validation-webform) | manual-only |

### 認証・認可・保護

| やりたいこと | Kata | Status |
|---|---|---|
| OAuth認証フローをResourceで実装する | [`auth-oauth-flow`](docs/source-index.md#auth-oauth-flow) | showcase |
| セッションログイン（login→callback→logout） | [`admin-session-login`](docs/source-index.md#admin-session-login) | showcase |
| 認証境界（401）とauthor-scoped認可（403）を分ける | [`admin-auth-boundary`](docs/source-index.md#admin-auth-boundary) | showcase |
| write操作をCSRF/Same-Originで保護する | [`csrf-same-origin-protection`](docs/source-index.md#csrf-same-origin-protection) | canonical |
| ログイン試行をレート制限する（429） | [`rate-limit-interceptor`](docs/source-index.md#rate-limit-interceptor) | external |
| ロール/権限でリソース単位の認可をする | [`resource-permission-authorization`](docs/source-index.md#resource-permission-authorization) | external |
| 有効期限付き署名URLで検証リンクを作る | [`signed-url-verification`](docs/source-index.md#signed-url-verification) | external |

### キャッシュと配信

| やりたいこと | Kata | Status |
|---|---|---|
| `#[Cacheable]` だけでleafをキャッシュする | [`cacheable-leaf`](docs/source-index.md#cacheable-leaf) | showcase |
| `#[Embed]` 子の更新で親cacheも無効化する | [`cache-embed-dependency`](docs/source-index.md#cache-embed-dependency) | showcase |
| body由来のN個の依存を `fromAssoc()` で宣言する | [`cache-body-derived-dependency`](docs/source-index.md#cache-body-derived-dependency) | showcase |
| write時にcollection cacheを `#[Purge]` する | [`cache-purge`](docs/source-index.md#cache-purge) | showcase |
| 部分キャッシュ（donut cache）を使う | [`donut-cache`](docs/source-index.md#donut-cache) | showcase |
| レスポンス全体をキャッシュしETagを付ける | [`cacheable-response`](docs/source-index.md#cacheable-response) | showcase |
| If-None-Matchに304で応える | [`conditional-request-304`](docs/source-index.md#conditional-request-304) | showcase |

### 実行モデル（並列・遅延・バッチ・CLI・ストリーム）

| やりたいこと | Kata | Status |
|---|---|---|
| ファイル/バイナリをストリームで返す | [`stream-response`](docs/source-index.md#stream-response) | showcase |
| `#[Embed]` graphを並列実行する | [`async-embed-parallel`](docs/source-index.md#async-embed-parallel) | showcase |
| 応答後にfollow-upを実行する（`#[Defer]`・202） | [`defer-resource-request`](docs/source-index.md#defer-resource-request) | showcase |
| 条件付きでdeferを手動制御する | [`defer-conditional`](docs/source-index.md#defer-conditional) | showcase |
| cron/queueワーカーをCommand Resourceにする | [`batch-command-resource`](docs/source-index.md#batch-command-resource) | external |
| ResourceをCLIコマンドとして公開する | [`cli-resource`](docs/source-index.md#cli-resource) | showcase |
| 他アプリのResourceをimportして呼ぶ | [`import-app`](docs/source-index.md#import-app) | showcase |

### Event Sourcing

| やりたいこと | Kata | Status |
|---|---|---|
| 観察ログからEventを抽出する | [`event-extraction`](docs/source-index.md#event-extraction) | showcase |
| Eventをフィルタしてreplayする | [`event-filter-replay`](docs/source-index.md#event-filter-replay) | showcase |
| Eventを永続化する | [`event-store-persistence`](docs/source-index.md#event-store-persistence) | support |
| Resource実行から観察ログを生成する | [`resource-observation-bridge`](docs/source-index.md#resource-observation-bridge) | showcase |

### テストする

| やりたいこと | Kata | Status |
|---|---|---|
| DBなしでMediaQueryをFakeする | [`fake-sql-query`](docs/source-index.md#fake-sql-query) | support |
| App ResourceのAPI contractをテストする | [`app-resource-test`](docs/source-index.md#app-resource-test) | support |
| Page ResourceのHTML contractをテストする | [`page-resource-test`](docs/source-index.md#page-resource-test) | support |
| Link/Embedを辿るworkflowをテストする | [`hypermedia-workflow-test`](docs/source-index.md#hypermedia-workflow-test) | support |
| 実DB経路を必要時だけ検証する | [`mysql-integration-test`](docs/source-index.md#mysql-integration-test) | support |

### 意味論と生成物（ALPS / schema / docs / AI）

| やりたいこと | Kata | Status |
|---|---|---|
| ALPS profileを意味のSSOTにする | [`alps-profile-ssot`](docs/source-index.md#alps-profile-ssot) | support |
| 決定的なfake dataを生成する | [`semantic-fake-data`](docs/source-index.md#semantic-fake-data) | support |
| 観測からJSON Schemaを生成する | [`json-schema-generated`](docs/source-index.md#json-schema-generated) | support |
| API docsとllms.txtを生成する | [`apidoc-llms-generated`](docs/source-index.md#apidoc-llms-generated) | support |
| ResourceをLLMのtool定義として公開する | [`tool-use-instrument`](docs/source-index.md#tool-use-instrument) | external |

## スコープ外（意図的に扱わない）

Production デプロイ / compile / preload、High-Performance Servers（Swoole / RoadRunner / FrankenPHP）、Aura.Router カスタムルーティングはこのリファレンスの対象外。理由と全リストは [docs/scope.md](docs/scope.md) を参照。

## スキルを獲得して使う

このリポジトリの価値は CMS を動かすことではなく、`bear-kata` スキルを獲得して **自分の BEAR.Sunday 実装に型(Kata)を適用する** ことにあります。

```bash
# 個人用（どのプロジェクトでも有効）
mkdir -p ~/.claude/skills && cp -r .claude/skills/bear-kata ~/.claude/skills/

# または特定プロジェクト用
mkdir -p /path/to/your-project/.claude/skills && cp -r .claude/skills/bear-kata /path/to/your-project/.claude/skills/
```

Claude Code で「記事一覧のページングを **kata に従って実装してください**」のように言うと `bear-kata` スキルが発動し、[ソース索引](docs/source-index.md)から該当 Kata（着手前チェック → Source / Tests → マスター確認）へ誘導します。`/bear-kata` で明示的に呼ぶこともできます。このリポジトリ内で開く場合はコピー不要です。

## ドキュメント

- [docs/source-index.md](docs/source-index.md) — 各KataのSource / Tests / Key points 詳細（この索引の本体）
- [docs/setup.md](docs/setup.md) — DB・サーバー起動・管理者ログイン
- [docs/architecture.md](docs/architecture.md) — BDRレイアウトと設計根拠
- [docs/conventions.md](docs/conventions.md) — 命名・Resourceパターン・Read/Write SQL契約
- [docs/scope.md](docs/scope.md) — 実装範囲と意図的な除外事項

## Links

- [BEAR.Sunday manual](https://bearsunday.github.io/manuals/1.0/en/index.html)
- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery)
- [ALPS](https://alps.io/)
