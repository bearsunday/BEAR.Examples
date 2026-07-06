# BEAR.Kata ソース索引

このページは、AIエージェントと人間が「これを実装したい時、このリファレンスのどこを見るか」を素早く引くためのソース索引です。チュートリアルではありません。説明は最小限にし、実装意図（intent）からソース、テスト、真似してよい要点へ直接到達することを目的にします。

BEAR.Kata の各エントリは「Kata（型）」です。武道の型と同じく、**着手前に型を確認し、実装後に型に従えたかを検証する**ことで、「このKataをマスターした」と自信を持って言える状態を目指します。

## エージェントの引き方（6ステップ）

```
1. INTENT    「BEAR.Sundayでページング一覧を実装したい」
               ↓ skill `bear-kata` が intent でトリガー（能動的入口）
2. ROUTE     skill → この索引 → kata `db-read-list-pager`
               ↓ Status=canonical なら真似してよい正規形
3. READ      Source（Resource + Query + SQL + Entity）+ Key points + Do not
               ↓ 【着手前チェック】型と前提を確認してから書く
4. OBSERVE   Tests = 正しい振る舞いの仕様
               ↓
5. IMPLEMENT 自分のプロジェクトに移植
               ↓
6. MASTER    【マスター確認】greppable assertion ＋ listされたTestを写経してgreen
               → 全項目 ✓ なら「このKataをマスターした」
```

## 前提

- namespace は `BEAR\Kata\`。`Source` と `Tests` はこのリポジトリ root からの相対パスです。
- 各Kataの `Manual:` は対応するBEAR.Sunday公式マニュアル章へのリンクです。型の背景にあるフレームワーク仕様はそちらが一次資料です。
- サンプルの位置付けは次の5種類です。

| Status | 意味 |
|---|---|
| `canonical` | 通常の実装で最初に真似する正規形 |
| `showcase` | 特定機能を切り出して見せる実例 |
| `comparison-only` | 比較理解用。デフォルト実装としてコピーしない |
| `support` | テスト、Fake、生成物など正規形を支える周辺実装 |
| `manual-only` | 公式マニュアルを一次資料とする型の記述のみ。このリポジトリに正規実装・テストはまだ無い |
| `external` | 外部公開リポジトリ（実働アプリ・未導入パッケージ）の参照実装を指す型の記述。このリポジトリに正規実装は無い |

## 使い方

1. `Aliases` にある語で検索します。例: `streaming`, `DbQuery`, `PRG`, `FakeSqlQuery`。
2. `Status` で、そのコードをコピーしてよい正規形か、比較用かを確認します。
3. **着手前チェック** で、書き始める前に守るべき型と前提を確認します。
4. `Source` を読みます。
5. `Tests` を読み、期待される振る舞いを確認します。
6. 実装後に **マスター確認** のチェックリストを自分のコードに対して走らせ、全項目が満たされたらそのKataをマスターしたと判断します。マスター確認は「`Tests` に挙げたテストを自分の実装へ写経して green になること」を最終確証とします。

`manual-only` と `external` のKataは手順が異なります。Sourceの代わりに一次資料（公式マニュアル章、または `Reference:` の外部リポジトリ）を読み、**近いKata** に挙げた実装済みKataの型（命名・分離・テスト形）を流用して移植します。マスター確認は自プロジェクトに書いたテストのgreenが最終確証です。

## 索引（一覧）

| Kata | Status | 何をするか |
|---|---|---|
| [`db-read-one-entity`](kata/db-read-one-entity.md) | canonical | DBから主キーで1件のEntityを読む |
| [`db-entity-factory`](kata/db-entity-factory.md) | canonical | `#[DbQuery(factory:)]`でDB行をEntityへ変換する |
| [`db-read-by-natural-key`](kata/db-read-by-natural-key.md) | canonical | natural keyで1件読む |
| [`db-read-list-pager`](kata/db-read-list-pager.md) | canonical | DBから一覧をページングして読む |
| [`db-command-write`](kata/db-command-write.md) | canonical | DB書き込みをCommand Interfaceに分ける |
| [`db-link-table-sync`](kata/db-link-table-sync.md) | canonical | link tableをclear/linkで同期する |
| [`db-result-projection`](kata/db-result-projection.md) | showcase | Query結果を専用Result objectにする |
| [`db-array-row-comparison`](kata/db-array-row-comparison.md) | comparison-only | Entityではなくarrayで読む比較例を見る |
| [`db-sqlquery-orchestration`](kata/db-sqlquery-orchestration.md) | comparison-only | `SqlQueryInterface`で複数SQLを調停する |
| [`db-raw-pdo-comparison`](kata/db-raw-pdo-comparison.md) | comparison-only | Raw PDOとの違いを見る |
| [`api-get-hal-resource`](kata/api-get-hal-resource.md) | canonical | GET ResourceをHAL+JSONで返す |
| [`api-post-input-dto`](kata/api-post-input-dto.md) | canonical | POST入力をInput DTOで受ける |
| [`api-put-tristate-input`](kata/api-put-tristate-input.md) | canonical | PUTでtri-state入力を扱う |
| [`api-delete-no-content`](kata/api-delete-no-content.md) | canonical | DELETE成功を204で返す |
| [`not-found-response`](kata/not-found-response.md) | canonical | 見つからないResourceを404にする |
| [`json-schema-validation`](kata/json-schema-validation.md) | canonical | Request/ResponseをJSON Schemaで検証する |
| [`hal-link`](kata/hal-link.md) | canonical | HAL `_links` を `#[Link]` で宣言する |
| [`hal-embed`](kata/hal-embed.md) | canonical | HAL `_embedded` を `#[Embed]` と `addQuery()` で作る |
| [`page-resource-qiq-detail`](kata/page-resource-qiq-detail.md) | canonical | Page Resourceで1件詳細HTMLを描画する |
| [`page-resource-list`](kata/page-resource-list.md) | canonical | Page Resourceで一覧HTMLを描画する |
| [`markdown-to-html`](kata/markdown-to-html.md) | canonical | Markdown本文をHTMLへ変換する |
| [`admin-prg-form`](kata/admin-prg-form.md) | showcase | Admin formでPRGを使う |
| [`stream-response`](kata/stream-response.md) | showcase | ファイルやバイナリをストリームで返す |
| [`cacheable-leaf`](kata/cacheable-leaf.md) | showcase | `#[Cacheable]` だけのleaf resourceを作る |
| [`cache-embed-dependency`](kata/cache-embed-dependency.md) | showcase | `#[Embed]` 親Resourceの依存を自動合成する |
| [`cache-body-derived-dependency`](kata/cache-body-derived-dependency.md) | showcase | body由来の可変長依存を `fromAssoc()` で宣言する |
| [`async-embed-parallel`](kata/async-embed-parallel.md) | showcase | embed graphを並列実行に載せる |
| [`cli-resource`](kata/cli-resource.md) | showcase | ResourceをCLIコマンドとして公開する |
| [`fake-sql-query`](kata/fake-sql-query.md) | support | DBなしでMediaQueryをFakeする |
| [`app-resource-test`](kata/app-resource-test.md) | support | App ResourceのAPI contractをテストする |
| [`page-resource-test`](kata/page-resource-test.md) | support | Page ResourceのHTML contractをテストする |
| [`hypermedia-workflow-test`](kata/hypermedia-workflow-test.md) | support | Link/Embedを辿るworkflowをテストする |
| [`mysql-integration-test`](kata/mysql-integration-test.md) | support | 実DB経路を必要時だけ検証する |
| [`alps-profile-ssot`](kata/alps-profile-ssot.md) | support | ALPS profileを意味のSSOTにする |
| [`semantic-fake-data`](kata/semantic-fake-data.md) | support | semantic-exで決定的fake dataを作る |
| [`json-schema-generated`](kata/json-schema-generated.md) | support | fake observationからJSON Schemaを生成する |
| [`apidoc-llms-generated`](kata/apidoc-llms-generated.md) | support | API docsとllms.txtを生成する |
| [`auth-oauth-flow`](kata/auth-oauth-flow.md) | showcase | OAuth認証フローをAuthInterface経由で示す |
| [`csrf-same-origin-protection`](kata/csrf-same-origin-protection.md) | canonical | CSRFトークン + Same-Origin interceptorをAOP bindする |
| [`file-upload-input`](kata/file-upload-input.md) | canonical | `#[InputFile]`でファイルアップロードを受ける |
| [`crawl-data-loader`](kata/crawl-data-loader.md) | showcase | `#[Link(crawl:...)]` + DataLoaderでN+1を解消する |
| [`state-transition-resource`](kata/state-transition-resource.md) | canonical | 状態遷移を独立Resourceとして切り出す |
| [`error-status-mapping`](kata/error-status-mapping.md) | canonical | 例外→HTTPステータスマッピングとエラーハンドリング |
| [`cache-purge`](kata/cache-purge.md) | showcase | `#[Purge]`でwrite時にcollection cacheを手動無効化する |
| [`donut-cache`](kata/donut-cache.md) | showcase | `#[DonutCache]`で部分キャッシュを示す |
| [`cacheable-response`](kata/cacheable-response.md) | showcase | `#[CacheableResponse]`でレスポンス全体をキャッシュする |
| [`conditional-request-304`](kata/conditional-request-304.md) | showcase | 条件付きリクエスト（If-None-Match → 304）で転送を省く |
| [`admin-auth-boundary`](kata/admin-auth-boundary.md) | showcase | 型で表現する認証境界とauthor-scoped認可 |
| [`admin-session-login`](kata/admin-session-login.md) | showcase | セッションOAuthログインフロー（login → callback → logout） |
| [`admin-confirm-page`](kata/admin-confirm-page.md) | showcase | 確認画面Page Resourceで状態遷移をラップする |
| [`import-app`](kata/import-app.md) | showcase | ImportAppModuleで他アプリのResourceを呼ぶ |
| [`event-extraction`](kata/event-extraction.md) | showcase | Semantic Logger観察ログからEventを抽出する |
| [`event-filter-replay`](kata/event-filter-replay.md) | showcase | Eventsをフィルタしてreplayする |
| [`event-store-persistence`](kata/event-store-persistence.md) | support | EventStoreInterfaceでEventを永続化する |
| [`resource-observation-bridge`](kata/resource-observation-bridge.md) | showcase | BEAR.Resource実行から観察ログを生成する |
| [`defer-resource-request`](kata/defer-resource-request.md) | showcase | `#[Defer]` + `#[Link]`で応答後にfollow-upを実行する |
| [`defer-conditional`](kata/defer-conditional.md) | showcase | `DeferInterface::add()`で条件付きdeferを手動制御する |
| [`api-patch-partial-update`](kata/api-patch-partial-update.md) | manual-only | PATCHで差分更新を受ける |
| [`api-options-method`](kata/api-options-method.md) | manual-only | OPTIONSでメソッドとパラメータ仕様を返す |
| [`content-negotiation`](kata/content-negotiation.md) | manual-only | AcceptヘッダでJSON/HTML/CSV等を出し分ける |
| [`form-validation-webform`](kata/form-validation-webform.md) | manual-only | Ray.WebFormModuleでAOPフォームバリデーション |
| [`web-context-param-binding`](kata/web-context-param-binding.md) | manual-only | Webコンテキスト値と他Resource値を引数に束縛する |
| [`db-transactional`](kata/db-transactional.md) | manual-only | `#[Transactional]`で複数書き込みを原子化する |
| [`aop-validation-valid`](kata/aop-validation-valid.md) | manual-only | `#[Valid]`/`#[OnValidate]`でAOPバリデーション |
| [`rate-limit-interceptor`](kata/rate-limit-interceptor.md) | external | `#[RateLimiter]`×interceptorで試行回数を制限する |
| [`resource-permission-authorization`](kata/resource-permission-authorization.md) | external | `#[RequiredPermission]`でリソース単位の権限を判定する |
| [`batch-command-resource`](kata/batch-command-resource.md) | external | バッチ/キューワーカーをCommand Resourceとして表現する |
| [`signed-url-verification`](kata/signed-url-verification.md) | external | 有効期限付き署名URLでメール検証リンクを実装する |
| [`tool-use-instrument`](kata/tool-use-instrument.md) | external | `#[Tool]`でResourceをLLMのtool定義として公開する |

## Resource / API

RESTメソッドはテーブルへのCRUDではなく、application stateへの操作です。各メソッドの安全性（safe=状態を変えない）と冪等性（idempotent=繰り返しても同じ結果）が、キャッシュ戦略とAI安全設計の両方を駆動します。

| Method | Safe | Idempotent | 意味 |
|---|---|---|---|
| GET | ✅ | ✅ | 状態を読む。自由にキャッシュ・AIから自由に呼べる |
| POST | — | — | 状態を変える。繰り返しは同じ結果を保証しない |
| PUT | — | ✅ | representation全体をURIに置く（無ければ作成） |
| PATCH | — | — | 差分を適用する |
| DELETE | — | ✅ | 削除する |
| OPTIONS | ✅ | ✅ | 必要パラメータと応答仕様を照会する |

また `#[Embed]` が埋め込むのはresourceの**結果**ではなくresourceへの**request**（=関係そのもの）です。この区別が、Resourceクラスを変えないままの並列実行（`async-embed-parallel`）・DataLoaderバッチ（`crawl-data-loader`）・部分キャッシュ（`donut-cache`）を可能にします。

## Runtime / representation

キャッシュ束（`cacheable-leaf` 〜 `conditional-request-304`）の前提は「キャッシュを無効化するのは時間（TTL）ではなくイベント（write）」です。着手前に1つだけ問うこと — そのresourceは本質的に静的（data resource。DBから読んでいても意味は静的）か、本質的に動的（計算過程自体が表現）か。前者ならcache Kataを適用し、後者にはcache属性を付けません。TTLを短くすることを戦略の代用にしないでください。

## Manual-only（型のみ記述 — 公式マニュアル準拠）

このセクションのKataは、BEAR.Sundayに機能が存在するがこのリポジトリに正規実装・テストがまだ無いものです。`Manual:` の公式マニュアル章を一次資料として読み、**近いKata** の実装済みの型（命名・分離・テスト形）を流用して移植します。マスター確認は自プロジェクトに書いたテストのgreenが最終確証です。

## External reference（外部参照実装の型）

このセクションのKataは、BEAR.Sundayの実装型として価値があるが、参照実装がこのリポジトリではなく外部の公開リポジトリにあるものです。

- 参照実装の主な出典は [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app)（DDD + CQRS構成の実働BEAR.Sundayアプリ。`Reference:` のパスは同リポジトリの `source/app/` 配下）と、公式パッケージ [bearsunday/BEAR.ToolUse](https://github.com/bearsunday/BEAR.ToolUse)。
- bear-appにはライセンス表記が無いため**コードをコピーしない**こと。attribute × interceptor の構成・命名・責務分割という「型」を読み取り、自プロジェクトで再実装します。
- bear-appはDDD層構造（Domain/Application/Infrastructure）を採用しており、このリポジトリのBDR（Bound / Domain / Resource）とはアーキテクチャの流儀が異なります。以下のKataはその流儀差に依存しない横断的な型のみを抽出しています。

