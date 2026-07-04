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

## 使い方

1. `Aliases` にある語で検索します。例: `streaming`, `DbQuery`, `PRG`, `FakeSqlQuery`。
2. `Status` で、そのコードをコピーしてよい正規形か、比較用かを確認します。
3. **着手前チェック** で、書き始める前に守るべき型と前提を確認します。
4. `Source` を読みます。
5. `Tests` を読み、期待される振る舞いを確認します。
6. 実装後に **マスター確認** のチェックリストを自分のコードに対して走らせ、全項目が満たされたらそのKataをマスターしたと判断します。マスター確認は「`Tests` に挙げたテストを自分の実装へ写経して green になること」を最終確証とします。

`manual-only` のKataは手順が異なります。Sourceの代わりに公式マニュアル章を読み、**近いKata** に挙げた実装済みKataの型（命名・分離・テスト形）を流用して移植します。マスター確認は自プロジェクトに書いたテストのgreenが最終確証です。

## 索引（一覧）

| Kata | Status | 何をするか |
|---|---|---|
| [`db-read-one-entity`](#db-read-one-entity) | canonical | DBから主キーで1件のEntityを読む |
| [`db-entity-factory`](#db-entity-factory) | canonical | `#[DbQuery(factory:)]`でDB行をEntityへ変換する |
| [`db-read-by-natural-key`](#db-read-by-natural-key) | canonical | natural keyで1件読む |
| [`db-read-list-pager`](#db-read-list-pager) | canonical | DBから一覧をページングして読む |
| [`db-command-write`](#db-command-write) | canonical | DB書き込みをCommand Interfaceに分ける |
| [`db-link-table-sync`](#db-link-table-sync) | canonical | link tableをclear/linkで同期する |
| [`db-result-projection`](#db-result-projection) | showcase | Query結果を専用Result objectにする |
| [`db-array-row-comparison`](#db-array-row-comparison) | comparison-only | Entityではなくarrayで読む比較例を見る |
| [`db-sqlquery-orchestration`](#db-sqlquery-orchestration) | comparison-only | `SqlQueryInterface`で複数SQLを調停する |
| [`db-raw-pdo-comparison`](#db-raw-pdo-comparison) | comparison-only | Raw PDOとの違いを見る |
| [`api-get-hal-resource`](#api-get-hal-resource) | canonical | GET ResourceをHAL+JSONで返す |
| [`api-post-input-dto`](#api-post-input-dto) | canonical | POST入力をInput DTOで受ける |
| [`api-put-tristate-input`](#api-put-tristate-input) | canonical | PUTでtri-state入力を扱う |
| [`api-delete-no-content`](#api-delete-no-content) | canonical | DELETE成功を204で返す |
| [`not-found-response`](#not-found-response) | canonical | 見つからないResourceを404にする |
| [`json-schema-validation`](#json-schema-validation) | canonical | Request/ResponseをJSON Schemaで検証する |
| [`hal-link`](#hal-link) | canonical | HAL `_links` を `#[Link]` で宣言する |
| [`hal-embed`](#hal-embed) | canonical | HAL `_embedded` を `#[Embed]` と `addQuery()` で作る |
| [`page-resource-qiq-detail`](#page-resource-qiq-detail) | canonical | Page Resourceで1件詳細HTMLを描画する |
| [`page-resource-list`](#page-resource-list) | canonical | Page Resourceで一覧HTMLを描画する |
| [`markdown-to-html`](#markdown-to-html) | canonical | Markdown本文をHTMLへ変換する |
| [`admin-prg-form`](#admin-prg-form) | showcase | Admin formでPRGを使う |
| [`stream-response`](#stream-response) | showcase | ファイルやバイナリをストリームで返す |
| [`cacheable-leaf`](#cacheable-leaf) | showcase | `#[Cacheable]` だけのleaf resourceを作る |
| [`cache-embed-dependency`](#cache-embed-dependency) | showcase | `#[Embed]` 親Resourceの依存を自動合成する |
| [`cache-body-derived-dependency`](#cache-body-derived-dependency) | showcase | body由来の可変長依存を `fromAssoc()` で宣言する |
| [`async-embed-parallel`](#async-embed-parallel) | showcase | embed graphを並列実行に載せる |
| [`cli-resource`](#cli-resource) | showcase | ResourceをCLIコマンドとして公開する |
| [`fake-sql-query`](#fake-sql-query) | support | DBなしでMediaQueryをFakeする |
| [`app-resource-test`](#app-resource-test) | support | App ResourceのAPI contractをテストする |
| [`page-resource-test`](#page-resource-test) | support | Page ResourceのHTML contractをテストする |
| [`hypermedia-workflow-test`](#hypermedia-workflow-test) | support | Link/Embedを辿るworkflowをテストする |
| [`mysql-integration-test`](#mysql-integration-test) | support | 実DB経路を必要時だけ検証する |
| [`alps-profile-ssot`](#alps-profile-ssot) | support | ALPS profileを意味のSSOTにする |
| [`semantic-fake-data`](#semantic-fake-data) | support | semantic-exで決定的fake dataを作る |
| [`json-schema-generated`](#json-schema-generated) | support | fake observationからJSON Schemaを生成する |
| [`apidoc-llms-generated`](#apidoc-llms-generated) | support | API docsとllms.txtを生成する |
| [`auth-oauth-flow`](#auth-oauth-flow) | showcase | OAuth認証フローをAuthInterface経由で示す |
| [`csrf-same-origin-protection`](#csrf-same-origin-protection) | canonical | CSRFトークン + Same-Origin interceptorをAOP bindする |
| [`file-upload-input`](#file-upload-input) | canonical | `#[InputFile]`でファイルアップロードを受ける |
| [`crawl-data-loader`](#crawl-data-loader) | showcase | `#[Link(crawl:...)]` + DataLoaderでN+1を解消する |
| [`state-transition-resource`](#state-transition-resource) | canonical | 状態遷移を独立Resourceとして切り出す |
| [`error-status-mapping`](#error-status-mapping) | canonical | 例外→HTTPステータスマッピングとエラーハンドリング |
| [`cache-purge`](#cache-purge) | showcase | `#[Purge]`でwrite時にcollection cacheを手動無効化する |
| [`donut-cache`](#donut-cache) | showcase | `#[DonutCache]`で部分キャッシュを示す |
| [`cacheable-response`](#cacheable-response) | showcase | `#[CacheableResponse]`でレスポンス全体をキャッシュする |
| [`conditional-request-304`](#conditional-request-304) | showcase | 条件付きリクエスト（If-None-Match → 304）で転送を省く |
| [`admin-auth-boundary`](#admin-auth-boundary) | showcase | 型で表現する認証境界とauthor-scoped認可 |
| [`admin-session-login`](#admin-session-login) | showcase | セッションOAuthログインフロー（login → callback → logout） |
| [`admin-confirm-page`](#admin-confirm-page) | showcase | 確認画面Page Resourceで状態遷移をラップする |
| [`import-app`](#import-app) | showcase | ImportAppModuleで他アプリのResourceを呼ぶ |
| [`event-extraction`](#event-extraction) | showcase | Semantic Logger観察ログからEventを抽出する |
| [`event-filter-replay`](#event-filter-replay) | showcase | Eventsをフィルタしてreplayする |
| [`event-store-persistence`](#event-store-persistence) | support | EventStoreInterfaceでEventを永続化する |
| [`resource-observation-bridge`](#resource-observation-bridge) | showcase | BEAR.Resource実行から観察ログを生成する |
| [`defer-resource-request`](#defer-resource-request) | showcase | `#[Defer]` + `#[Link]`で応答後にfollow-upを実行する |
| [`defer-conditional`](#defer-conditional) | showcase | `DeferInterface::add()`で条件付きdeferを手動制御する |
| [`api-patch-partial-update`](#api-patch-partial-update) | manual-only | PATCHで差分更新を受ける |
| [`api-options-method`](#api-options-method) | manual-only | OPTIONSでメソッドとパラメータ仕様を返す |
| [`content-negotiation`](#content-negotiation) | manual-only | AcceptヘッダでJSON/HTML/CSV等を出し分ける |
| [`form-validation-webform`](#form-validation-webform) | manual-only | Ray.WebFormModuleでAOPフォームバリデーション |
| [`web-context-param-binding`](#web-context-param-binding) | manual-only | Webコンテキスト値と他Resource値を引数に束縛する |
| [`db-transactional`](#db-transactional) | manual-only | `#[Transactional]`で複数書き込みを原子化する |
| [`aop-validation-valid`](#aop-validation-valid) | manual-only | `#[Valid]`/`#[OnValidate]`でAOPバリデーション |

## Data access / BDR

### `db-read-one-entity`

**DBから主キーで1件のEntityを読む**

- **ID:** `db-read-one-entity`
- **Aliases:** read one row, fetch entity, primary key lookup, item query, `#[DbQuery]`, BDR read, article detail, Ray.MediaQuery, 1件取得, 主キー検索, エンティティ取得
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** 主キーで1件取得し、型付きEntityとしてResourceで使いたい。
- **着手前チェック（Before）:**
  - [ ] read用の `<Entity>QueryInterface` を、write用Commandと分けて用意したか。
  - [ ] 1件取得methodを `item(int $id): <Entity>|null` のシグネチャにするか決めたか。
  - [ ] SQLは `<entity>_item.sql` という命名でファイルに置くと決めたか。
  - [ ] `SELECT` のカラム順を hydration 先の引数順（`factory:` 指定時は factory method、無指定時は Entity constructor）に合わせる前提を理解したか（いずれも `PDO::FETCH_FUNC` で位置渡し）。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Query/ArticleQueryInterface.php::item()`
  - `src/Query/AuthorQueryInterface.php::item()`
  - `src/Entity/Article.php`
  - `var/db/sql/article_item.sql`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** `item(int $id): Article|null`、SQLファイルは `article_item.sql`、ResourceはSQLを直接持たない。constructor直渡しできるEntity（`Author` 等）は素の `#[DbQuery]`、enum変換・日付正規化が要るEntityは `db-entity-factory` の型で `factory:` を指定する。
- **Do not:** Resource内にSQLを書く。templateからDBを読む。
- **マスター確認（After）:**
  - [ ] Resource class に SQL 文字列が無い（`grep -i select src/Resource/App/<Name>.php` が空）。
  - [ ] Query method が `item(int $id): <Entity>|null` 型を返す。
  - [ ] `var/db/sql/<entity>_item.sql` が存在し、`SELECT` カラム順が hydration 先（factory method / Entity constructor）の引数順と一致。
  - [ ] `ArticleTest.php` 相当を写経し、存在IDで200・型付きbody、未存在IDで404を pin して green。

### `db-entity-factory`

**`#[DbQuery(factory:)]`でDB行をEntityへ変換する**

- **ID:** `db-entity-factory`
- **Aliases:** entity factory, DbQuery factory, `factory:`, enum hydration, date normalization, ArticleFactory, fromRow, fromRows, row mapping, ファクトリ変換, hydration
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** DB行をconstructor直渡しできないEntity（enum・日付正規化・派生値を持つ）に変換したい。
- **着手前チェック（Before）:**
  - [ ] Entityがenum（`ArticleStatus::from()`）や日付正規化などDB行に無い変換を必要とするか確認したか（不要なら素の `#[DbQuery]` — `db-read-one-entity` の型）。
  - [ ] 変換をResourceやEntityではなく `src/Factory/<Entity>Factory.php` に置くと決めたか。
  - [ ] `factory()` メソッドの引数順をSELECTカラム順に合わせる前提を理解したか（素のfetchのconstructor引数順ルールがfactoryメソッドに移る）。
  - [ ] Pager経由のlist（snake_caseキーの連想配列row）用に `fromRow()` / `fromRows()` を用意するか決めたか。
- **Source:**
  - `src/Factory/ArticleFactory.php`
  - `src/Query/ArticleQueryInterface.php::item()`
  - `src/Entity/ArticleStatus.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Integration/ArticleMySQLTest.php`
- **Key points:** `#[DbQuery('article_item', factory: ArticleFactory::class)]` でhydrationをfactoryに委譲（fetchは `FetchInjectionFactory`、同じく `PDO::FETCH_FUNC` の位置渡し）。`factory()` は単一row（引数順=SELECTカラム順）、`fromRows()` はPager由来の連想配列list用。enum再構築・日付正規化はfactory 1箇所に集約する。
- **Do not:** 変換ロジックをResourceに書かない。素のconstructor fetchで足りるEntity（`Author`/`Category` 等）にfactoryを増やさない。
- **マスター確認（After）:**
  - [ ] enum/日付変換が factory に集約され、Resource側に `::from(` や日付整形が無い。
  - [ ] `factory()` の引数順が `<entity>_item.sql` のSELECTカラム順と一致。
  - [ ] `ArticleTest.php` 相当で型付きEntity（enum property含む）が返ることを green。

### `db-read-by-natural-key`

**natural keyで1件読む**

- **ID:** `db-read-by-natural-key`
- **Aliases:** natural key lookup, bySlug, byEmail, byFilename, after insert lookup, unique key read, 自然キー, 一意キー, INSERT後のID回収, lastInsertIdを使わない
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** clientが指定した一意な値で再取得したい。特にINSERT後に新規IDを回収したい。
- **着手前チェック（Before）:**
  - [ ] 対象Entityに slug / email / filename のような自然キー（一意制約）があるか確認したか。
  - [ ] 取得methodを `by<NaturalKey>()`（`bySlug`/`byEmail`/`byFilename`）と命名すると決めたか。
  - [ ] INSERT後の新規ID回収を `lastInsertId()` ではなく自然キー再SELECTで行う方針を理解したか。
  - [ ] INSERT直後の `by<NaturalKey>()` がnullを返す異常系は `assert` で表面化させる方針か確認したか（`Article::onPost()` の `assert($created !== null)` 参照）。
- **Source:**
  - `src/Query/ArticleQueryInterface.php::bySlug()`
  - `src/Resource/App/Article.php::onPost()`
  - `var/db/sql/article_by_slug.sql`
  - `src/Query/AuthorQueryInterface.php::byEmail()`
  - `var/db/sql/author_by_email.sql`
  - `src/Query/MediaQueryInterface.php::byFilename()`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Smoke/FakeSqlQueryTest.php`
- **Key points:** INSERT後は `lastInsertId()` ではなく、`bySlug()` などのnatural keyで再SELECTする。Ray.MediaQueryには `InsertedRow` 戻り値（auto-increment id回収）もあるが、本Kataの正規経路はdriver非依存でFakeでも決定的な自然キー再SELECT（`docs/conventions.md` §4 After-INSERT id）。
- **Do not:** driver依存のID状態をResourceの標準経路に持ち込む。
- **マスター確認（After）:**
  - [ ] write path に `lastInsertId` が登場しない（`grep -ri lastinsertid src/` が空）。
  - [ ] `by<Key>()` method と `<entity>_by_<key>.sql` が対応して存在。
  - [ ] POST後に自然キーで再取得し新規IDを body へ返すフローを `ArticleTest.php` 相当で green。

### `db-read-list-pager`

**DBから一覧をページングして読む**

- **ID:** `db-read-list-pager`
- **Aliases:** list query, collection resource, pager, `PagesInterface`, `#[Pager]`, article list, filtering, ページング, ページネーション, 一覧取得, 絞り込み, ページャ
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** collection resourceで一覧、絞り込み、ページングを扱いたい。
- **着手前チェック（Before）:**
  - [ ] item resource（1件）と collection resource（一覧）を別Resourceに分けると決めたか。
  - [ ] 一覧methodを `list(...)` と命名し、SQLを `<entity>_list.sql` に置くと決めたか。
  - [ ] ページングを `#[Pager(perPage: 'perPage')]` と `PagesInterface` で扱う前提を理解したか（`perPage` はint固定値と引数名stringの両対応）。
  - [ ] `page` / `perPage` の範囲外入力をResource側でclampすると決めたか（`Articles::onGet()` はperPage上限100・範囲外pageは最終ページへ丸める）。
- **Source:**
  - `src/Resource/App/Articles.php::onGet()`
  - `src/Query/ArticleQueryInterface.php::list()`
  - `src/Factory/ArticleFactory.php::fromRows()`
  - `var/db/sql/article_list.sql`
  - `src/Resource/Page/ArticleList.php::onGet()`
- **Tests:**
  - `tests/Resource/App/ArticlesTest.php`
  - `tests/Resource/Page/ArticleListTest.php`
  - `tests/Smoke/MediaQuerySamplesTest.php`
- **Key points:** collectionは `list()`、SQLは `article_list.sql`、`perPage` は `#[Pager(perPage: 'perPage')]` と対応する。`count($pages)` でCOUNT SQL、`$pages[$page]` でそのページのSQLが遅延実行される。pager経路の `$pages[$page]->data` はsnake_caseキーの連想配列で返るため、Resource側で `ArticleFactory::fromRows()` によりEntityへ変換する。
- **Do not:** item resourceに一覧責務を混ぜる。template側でページング計算を始める。
- **マスター確認（After）:**
  - [ ] 一覧Resourceが item Resourceと別クラスになっている。
  - [ ] `list()` method の戻り値が `PagesInterface`、`#[Pager]` の `perPage` 名が parameter 名と一致。
  - [ ] filter（categoryId/tagId/status 等）省略時と指定時の件数差を `ArticlesTest.php` 相当で green。
  - [ ] perPage上限clampと範囲外pageの最終ページclampを `ArticlesTest.php` の該当ケース相当で green。

### `db-command-write`

**DB書き込みをCommand Interfaceに分ける**

- **ID:** `db-command-write`
- **Aliases:** write command, command interface, POST, PUT, DELETE, add update delete, CQRS split, AffectedRows, InsertedRow, 書き込み, 更新系, コマンド分離
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** ResourceからDBの作成、更新、削除を行いたい。
- **着手前チェック（Before）:**
  - [ ] read を `<Entity>QueryInterface`、write を `<Entity>CommandInterface` に分離すると決めたか。
  - [ ] write method を `add` / `update` / `delete` の命令形にすると決めたか。
  - [ ] write method の戻り値を `void`（または `AffectedRows`）にする前提を理解したか。
  - [ ] Resource依存名は read=queryable noun（`$article`）、write=`Cmd` 接尾辞（`$articleCmd`）にすると決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onPost()`
  - `src/Resource/App/Article.php::onPut()`
  - `src/Resource/App/Article.php::onDelete()`
  - `src/Query/ArticleCommandInterface.php`
  - `src/Query/Samples/ArticleAffectedRowsCommandInterface.php`
  - `var/db/sql/article_add.sql`
  - `var/db/sql/article_update.sql`
  - `var/db/sql/article_delete.sql`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Integration/ArticleMySQLTest.php`
  - `tests/Smoke/MediaQuerySamplesTest.php`
- **Key points:** readは `<Entity>QueryInterface`、writeは `<Entity>CommandInterface` に分ける。write methodは `add`, `update`, `delete` の命令形。同じSQL idでも宣言した戻り値型だけで挙動が切り替わる：`void`=実行のみ / `AffectedRows`=影響行数 / `InsertedRow`=auto-increment idと解決済み値。影響行数が要る遷移系writeの実例は `ArticleCommandInterface::publish(): AffectedRows`。
- **Do not:** read/write methodを同じinterfaceに混ぜる。
- **マスター確認（After）:**
  - [ ] `<Entity>QueryInterface` に write method が、`<Entity>CommandInterface` に read method が混ざっていない。
  - [ ] write method 名が命令形（add/update/delete）で SQL ファイル名と対応。
  - [ ] POST/PUT/DELETE の各経路を `ArticleTest.php` 相当で green。

### `db-link-table-sync`

**link tableをclear/linkで同期する**

- **ID:** `db-link-table-sync`
- **Aliases:** many-to-many, tagIds, link table, clear links, replace relation, article tags, 多対多, 中間テーブル, 関連テーブル, リレーション置換
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** 記事とタグのような関連テーブルを、入力されたIDリストに置き換えたい。
- **着手前チェック（Before）:**
  - [ ] 多対多の更新を「全削除→再リンク」の置換戦略で行うと決めたか。
  - [ ] link用Commandに `clear($parentId)` と `link($parentId, $childId)` を用意すると決めたか。
  - [ ] link tableのSQLをResourceに書かず `<rel>_clear.sql` / `<rel>_link.sql` に置くと決めたか。
  - [ ] 新しいwrite SQL id（`<rel>_clear` / `<rel>_link`）を `tests/Fake/FakeSqlQuery.php` の `WRITE_SQL_IDS` allowlistへ登録すると理解したか（`DbQueryInterceptor` はwriteもgetRow/getRowList経由で呼ぶ）。
- **Source:**
  - `src/Resource/App/Article.php::syncTags()`
  - `src/Query/ArticleTagCommandInterface.php`
  - `var/db/sql/article_tag_clear.sql`
  - `var/db/sql/article_tag_link.sql`
  - `tests/Fake/FakeSqlQuery.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Integration/ArticleMySQLTest.php`
- **Key points:** relation更新は `clear($articleId)` 後に `link($articleId, $tagId)` を繰り返す。PUTでは tri-state入力と連動する：`tagIds === null` は触らない、`[]` は全解除、リスト指定は置換（`api-put-tristate-input` 参照）。
- **Do not:** Resource内でlink tableのSQLを直接組み立てる。
- **マスター確認（After）:**
  - [ ] Resource に link table の `INSERT`/`DELETE` 文字列が無い。
  - [ ] 更新フローが `clear()` → `link()` ループになっている。
  - [ ] tagIds を別リストに変更した時に関連が置換されることを `ArticleTest.php`（tagIds `[1,2,3]`→`[4,5]` の置換ケース）相当で green。

### `db-result-projection`

**Query結果を専用Result objectにする**

- **ID:** `db-result-projection`
- **Aliases:** query projection, SELECT result, collection wrapper, typed collection, `PostQueryInterface`, fromContext, PostQueryContext, 型付きコレクション, 射影
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** `list<Entity>` の素配列ではなく、`published()` / `titles()` のような意図を表すnamed methodを持つ型付きcollection wrapperでSELECT結果を返したい。
- **着手前チェック（Before）:**
  - [ ] 表示用の加工（絞り込み、整形）を汎用Entityやtemplateに入れたくない理由を明確にしたか。
  - [ ] Result objectを `PostQueryInterface` 実装にし、named method（`published()` 等）で意図を表すと決めたか。
- **Source:**
  - `src/Query/ArticleSelectionQueryInterface.php`
  - `src/Result/ArticleSelection.php`
  - `src/Factory/ArticleFactory.php`
  - `var/db/sql/article_selection_list.sql`
- **Tests:**
  - `tests/Smoke/MediaQuerySamplesTest.php`
- **Key points:** `ArticleSelection::published()` のようなnamed methodでtemplate側の条件分岐を減らす。Result objectは `IteratorAggregate` / `Countable` を実装し、`fromContext()` で構築する。wrapper内の行のhydrationはEntity listと同じ仕組み（ここでは `factory: ArticleFactory::class`）。`AffectedRows` / `InsertedRow` も同じ `PostQueryInterface` 機構の実装であり、カスタムDML resultも同機構で作れる。
- **Do not:** presentation専用の加工を汎用Entityやtemplateに押し込む。
- **マスター確認（After）:**
  - [ ] Result class が `PostQueryInterface` を実装し、表示意図を表す named method を持つ。
  - [ ] template / Resource 側に同じ絞り込みロジックが重複していない。
  - [ ] `MediaQuerySamplesTest.php` 相当（`titles()` / `published()->count()` / `first()`）を写経して green。

### `db-array-row-comparison`

**Entityではなくarrayで読む比較例を見る**

- **ID:** `db-array-row-comparison`
- **Aliases:** array row, no entity, row array, `type: row`, migration comparison, 連想配列, 配列で受け取る, Entityなし
- **Status:** `comparison-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** Entityを使わない実装と正規形の責務差を理解したい。
- **着手前チェック（Before）:**
  - [ ] これは正規形ではなく**比較学習用**であると理解したか（デフォルト採用しない）。
  - [ ] 何を比較したいか（型変換・日付正規化・row shape防衛がどこに寄るか）を意識したか。
  - [ ] `#[DbQuery(type: 'row')]` は単一行を連想配列で直接受ける指定であり、無指定の `array` 戻り値はrowlist（単一行でも `$result[0]` に入る）と理解したか。
- **Source:**
  - `src/Resource/App/Variations/ArticleAsArray.php`
  - `src/Query/Variations/ArticleAsArrayQueryInterface.php`
  - `var/db/sql/article_as_array_item.sql`
- **Tests:**
  - `tests/Resource/App/Variations/ArticleAsArrayTest.php`
- **Key points:** 型変換、日付正規化、row shapeの防衛がResource側に寄ることを確認する。
- **Do not:** 長期保守の標準形としてarray rowを無条件に選ばない。
- **マスター確認（After）:**
  - [ ] array実装で増える防衛コード（型・日付・キー存在）を列挙し、Entity版と比較できた。
  - [ ] このパターンを標準採用しない理由を自分の言葉で説明できる。

### `db-sqlquery-orchestration`

**`SqlQueryInterface`で複数SQLを調停する**

- **ID:** `db-sqlquery-orchestration`
- **Aliases:** `SqlQueryInterface`, multi query, previous next article, reading time, programmatic query, getRow, 複数クエリ, 前後記事, 直接実行
- **Status:** `comparison-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.media.html
- **Use when:** 1つの `#[DbQuery]` methodに収まらない複数SQLの調停を理解したい。
- **着手前チェック（Before）:**
  - [ ] これは比較学習用で、単純な1件取得には使わないと理解したか。
  - [ ] 標準形は Query Interface + `#[DbQuery]`、これは複数SQL調停が必要な時のみと区別できたか。
  - [ ] 新しいsqlIdを足す時は `tests/Fake/FakeSqlQuery.php::getRow()` のmatch分岐にも追加すると理解したか。
- **Source:**
  - `src/Resource/App/Variations/ArticleSqlQuery.php`
  - `var/db/sql/article_sqlquery_item.sql`
  - `var/db/sql/article_sqlquery_previous.sql`
  - `var/db/sql/article_sqlquery_next.sql`
- **Tests:**
  - `tests/Resource/App/Variations/ArticleSqlQueryTest.php`
- **Key points:** `SqlQueryInterface::getRow()` をResourceが直接呼び、複数queryの結果を組み立てる。
- **Do not:** 単純な1件取得まで `SqlQueryInterface` に寄せない。標準形はQuery Interface + `#[DbQuery]`。
- **マスター確認（After）:**
  - [ ] `SqlQueryInterface` を直接使う妥当な条件（複数SQLの調停）を説明できる。
  - [ ] 単純取得を `#[DbQuery]` に戻すべき境界を判断できる。

### `db-raw-pdo-comparison`

**Raw PDOとの違いを見る**

- **ID:** `db-raw-pdo-comparison`
- **Aliases:** raw PDO, `ExtendedPdoInterface`, inline SQL, framework comparison, MediaQuery responsibility, Aura.Sql, fetchOne, 生PDO, 低レベル比較
- **Status:** `comparison-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.aura.html
- **Use when:** Ray.MediaQueryが外部化している責務を低レベル比較で理解したい。
- **着手前チェック（Before）:**
  - [ ] これは比較学習用で、正規のApp Resourceにinline SQLを戻さないと理解したか。
  - [ ] Ray.MediaQueryが肩代わりしている責務（SQL外部化・bind・fetch・型変換）を意識したか。
- **Source:**
  - `src/Resource/App/Variations/ArticleRawPdo.php`
  - `tests/Fake/FakeExtendedPdoProvider.php`
- **Tests:**
  - `tests/Resource/App/Variations/ArticleRawPdoTest.php`
- **Key points:** SQL、bind、fetch、型変換、日付正規化がResource近くに現れる。`ExtendedPdoInterface` はAuraSqlModuleのDIでconstructor注入される。`fetchOne()` は行が無いと `false` を返すため404分岐は `=== false` になる — MediaQuery正規形の `Entity|null` とは欠損の表現が異なる。
- **Do not:** 正規のApp Resourceにinline SQLを戻さない。
- **マスター確認（After）:**
  - [ ] Raw PDO版でResourceに現れる5つの責務（SQL/bind/fetch/型変換/日付）を指摘できる。
  - [ ] それらがMediaQuery正規形ではどこへ移るか説明できる。

## Resource / API

### `api-get-hal-resource`

**GET ResourceをHAL+JSONで返す**

- **ID:** `api-get-hal-resource`
- **Aliases:** GET resource, HAL JSON, ResourceObject body, API item resource, `onGet`, HAL+JSON応答, リソース取得, HALレンダリング
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** App Resourceで1件の状態をAPI表現として返したい。
- **着手前チェック（Before）:**
  - [ ] ResourceObject は状態を `$this->body` に置き、表現（JSON化）は renderer に任せると理解したか。
  - [ ] `#[Embed]` を使う場合は scalar を `$this->body += [...]`、使わない場合は `$this->body = [...]` と決めたか。
  - [ ] not-found 分岐を先に決めたか（`Code::NOT_FOUND` + message body、`not-found-response` 参照）。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/App/Author.php::onGet()`
  - `docs/resources.md`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** ResourceObjectは状態を `$this->body` に置く。表現はrendererが作る。`_links` / `_embedded` は `$ro->body` には現れず、HAL rendererが表現生成時に付与する — テストでは `json_decode((string) $ro, true)` でrendered表現を検証する。
- **Do not:** ResourceでJSON文字列を手作りしない。
- **マスター確認（After）:**
  - [ ] Resource に `json_encode` や手書きJSON文字列が無い。
  - [ ] body が連想配列（または `#[Embed]` slot 付き）で構成されている。
  - [ ] `ArticleTest.php` 相当で200 + body shape を pin して green。

### `api-post-input-dto`

**POST入力をInput DTOで受ける**

- **ID:** `api-post-input-dto`
- **Aliases:** POST resource, create resource, Input DTO, `#[Input]`, Ray.InputQuery, request DTO, 入力DTO, 作成API, 201 Created, Location header
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** 入力項目が多い作成処理を、Resource methodの前でDTO化したい。
- **着手前チェック（Before）:**
  - [ ] 入力が「多数 / tri-state / まとまった名前付きshape」のどれかで、DTO化が妥当か判断したか（短いflat入力なら scalar parameter のままでよい。ただし単一値でも `trim()` 等の正規化をconstructorに閉じたい場合はDTO化してよい）。
  - [ ] DTOを `src/Input/<Entity><Verb>Input.php` に置き、`#[Input]` で受けると決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onPost()`
  - `src/Input/ArticleCreateInput.php`
  - `var/json_validate/article_create.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** Resource method parameterに `#[Input] ArticleCreateInput $input` を置く。schema validationは `#[JsonSchema(params: ...)]`。失敗モードは2層 — 必須fieldの欠落はDTO生成時の `ParameterException`、schema違反（pattern等）は `ValidationException`（いずれも400）。成功時は `Code::CREATED`（201）+ `Location` header、新idは自然キー再SELECTで回収（`db-read-by-natural-key`）。Ray.InputQueryはネストDTOや `#[Input(item: ...)]` のobject array入力にも対応する。
- **Do not:** 多数の関連する入力を無理にflat scalar parameterへ増やし続けない。
- **マスター確認（After）:**
  - [ ] method signature が `#[Input] <Entity>CreateInput $input` になっている。
  - [ ] DTOの境界と `*_create.json` validation schema が同じ項目集合を守る。
  - [ ] 正常作成（201 + Location）と検証エラー（必須欠落 / pattern違反）の両方を `ArticleTest.php` 相当で green。

### `api-put-tristate-input`

**PUTでtri-state入力を扱う**

- **ID:** `api-put-tristate-input`
- **Aliases:** PUT resource, update resource, tri-state, nullable array, tagIds, leave clear replace, 更新API, 3状態入力, タグ置換, 省略時維持
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** 省略、空配列、非空配列で異なる意味を持つ更新入力を扱いたい。
- **着手前チェック（Before）:**
  - [ ] 「省略（維持）/ 空（全削除）/ 非空（置換）」の3状態を区別する必要があるか確認したか。
  - [ ] `null` と `[]` を同一視しないと決めたか。DTOはnativeな `array|null` typed parameterで受けると決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onPut()`
  - `src/Input/ArticleUpdateInput.php`
  - `var/json_validate/article_update.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** `tagIds === null` は維持、`[]` は全削除、listは置換。DTOはnative `array|null $tagIds` で受け、constructorで `array_values()` によりlistへ正規化する。非arrayのmalformed入力はRay.InputQueryがresource boundaryで `ParameterException`（400）として拒否する。validation schema側も同じtri-stateを宣言する — `article_update.json` は `"tagIds": {"type": ["array", "null"]}`（create側は `"type": "array"` でnull不可）。
- **Do not:** `null` と `[]` を同じ意味に潰さない。
- **マスター確認（After）:**
  - [ ] DTO が `null` / `[]` / 非空list の3分岐を保持している。
  - [ ] 非空リストでの置換と、非array入力の400拒否を `ArticleTest.php` 相当で green（省略=維持 / `[]`=全削除も自分の実装ではテストに含めるとよい）。

### `api-delete-no-content`

**DELETE成功を204で返す**

- **ID:** `api-delete-no-content`
- **Aliases:** DELETE resource, no content, 204, delete command, not found before delete, 削除API, 冪等削除
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** App Resourceで削除操作を公開したい。
- **着手前チェック（Before）:**
  - [ ] 削除前に存在確認を行い、未存在は404にすると決めたか。
  - [ ] 成功時は `Code::NO_CONTENT`（204）と空bodyを返すと決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onDelete()`
  - `src/Query/ArticleCommandInterface.php::delete()`
  - `var/db/sql/article_delete.sql`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** 削除前に存在確認し、成功時は `Code::NO_CONTENT` と空body。`onDelete` は `#[Purge(uri: 'app://self/articles')]` を伴い、キャッシュ済みcollectionをwrite時に無効化する（`cache-purge` 参照）。
- **Do not:** 削除済みや未存在を成功扱いにしない。
- **マスター確認（After）:**
  - [ ] 成功時 `$this->code = Code::NO_CONTENT` かつ body が空。
  - [ ] 未存在IDの削除が404になることを `ArticleTest.php` 相当で green。
  - [ ] 削除後の同一id GETが404になるround-tripも pin する。

### `not-found-response`

**見つからないResourceを404にする**

- **ID:** `not-found-response`
- **Aliases:** 404, not found, missing entity, error body, not found branch, 404応答, 見つからない, エラーページ
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** Queryが `null` を返した時にResourceで404を返したい。
- **着手前チェック（Before）:**
  - [ ] not-found を例外ではなくResource側で404 bodyとして表現すると決めたか。
  - [ ] Page template は4xxでも呼ばれるため guard を置くと理解したか。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/Page/Article.php::onGet()`
  - `templates/Page/Article.php`
  - `src/Renderer/CmsQiqRenderer.php`
  - `templates/Error.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** not-found readは例外ではなくResourceで404 bodyを置く。Page templateは4xxでも呼ばれるためguardを置く — guardは `ArticleNotFoundException` をthrowし、`CmsQiqRenderer::render()` がcatchしてError templateを描画する（code>=500は本文templateを呼ばず即Error描画）。
- **Do not:** `src/` からgeneric runtime exceptionを投げてnot-found表現にしない。
- **マスター確認（After）:**
  - [ ] `src/` に not-found用の generic `throw new \RuntimeException` 等が無い（必要なら `src/Exception/` のドメイン例外）。
  - [ ] Page template に entity 不在時の guard がある。
  - [ ] 未存在IDで App=404 / Page=Error template描画（本文templateが現れない）を両testで green。

### `json-schema-validation`

**Request/ResponseをJSON Schemaで検証する**

- **ID:** `json-schema-validation`
- **Aliases:** `#[JsonSchema]`, response schema, params schema, validation, json_validate, json_schema, JSONスキーマ, スキーマ検証, 入力検証, バリデーション
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** Resourceの入力と出力のshapeを宣言的に固定したい。
- **着手前チェック（Before）:**
  - [ ] response schema は `schema:`、request params schema は `params:` に分けると理解したか。
  - [ ] DTO境界とvalidation schemaを同じ項目集合で揃えると決めたか。
- **Source:**
  - `src/Resource/App/Article.php`
  - `src/Module/AppModule.php`
  - `var/json_schema/article.json`
  - `var/json_validate/article_create.json`
  - `var/json_validate/article_update.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`
- **Key points:** response schemaは `schema:`、request params schemaは `params:`。DTOとschemaは同じ境界を守る。違反は起点で例外が分かれる — request違反は `JsonSchemaRequestException`（400・client error）、response違反は `JsonSchemaResponseException`（500・server bug）。schemaの `errorMessage` key（ajv-errors規約）がfield別メッセージのSSOT。`#[JsonSchema]` には `key:`（bodyのindex key）と `target: 'view'`（描画後representationを検証）のオプションもある。
- **Do not:** Resource body shapeをテストやschemaなしで暗黙に変えない。
- **マスター確認（After）:**
  - [ ] method に `#[JsonSchema(schema: ..., params: ...)]` が付き、対応する `var/json_schema` / `var/json_validate` ファイルが存在。
  - [ ] body shape を変えた時は schema も更新され、`ArticleTest.php` 相当が green。

### `hal-link`

**HAL `_links` を `#[Link]` で宣言する**

- **ID:** `hal-link`
- **Aliases:** HAL link, `_links`, `#[Link]`, affordance, Choreography rel, URI template, リンク, 遷移, ハイパーメディア, URIテンプレート
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_link.html
- **Use when:** clientが次に遷移できるResourceをHAL linkとして表したい。
- **着手前チェック（Before）:**
  - [ ] link rel に ALPS Choreography 名（`goArticleList` 等の遷移名）を使うと決めたか。
  - [ ] link（遷移名）と embed（Taxonomy名詞）の命名層を混ぜないと理解したか。
  - [ ] `#[Link]` の `href` URI templateは **`$this->body` の値**で展開されると理解したか（`#[Embed]` の `src` がrequest引数で束縛されるのと対照的）。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/App/Articles.php::onGet()`
  - `var/alps/profile.json`
- **Tests:**
  - `tests/Hypermedia/ReaderBrowsesByCategoryTest.php`
  - `tests/Hypermedia/ReaderBrowsesByTagTest.php`
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** link relはALPS Choreography名。例: `goArticleList`, `goAuthor`, `goCategory`。`self` linkはHAL rendererが自動付与する。条件付き・動的linkは `$this->body['_links'][$rel] = ['href' => ..., 'templated' => true]` で足せる。
- **Do not:** embed用のtaxonomy名とlink用のchoreography名を混ぜない。
- **マスター確認（After）:**
  - [ ] `#[Link(rel: ...)]` の rel が ALPS profile の Choreography 名と一致。
  - [ ] `_links` の href **展開値**（bodyのどのkeyで展開されたか）まで pin している。
  - [ ] response の `_links` を辿る workflow test（`ReaderBrowsesBy*Test.php` 相当）が green。

### `hal-embed`

**HAL `_embedded` を `#[Embed]` と `addQuery()` で作る**

- **ID:** `hal-embed`
- **Aliases:** HAL embed, `_embedded`, `#[Embed]`, `addQuery`, embedded resource, Taxonomy rel, 埋め込み, リソース埋め込み, リソースグラフ, 関連リソース
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_link.html
- **Use when:** primary resourceの応答に関連Resourceを埋め込みたい。
- **着手前チェック（Before）:**
  - [ ] embed rel に Taxonomy 名詞（`author`/`category`/`tagList` 等）を使うと決めたか。
  - [ ] `#[Embed]` が先に Request slot を作るため、scalar は `$this->body += [...]` で足すと理解したか。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/App/Author.php::onGet()`
  - `src/Resource/App/Category.php::onGet()`
  - `src/Resource/App/Tags.php::onGet()`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** `#[Embed]` が先にRequest slotを作るため、scalar fieldsは `$this->body += [...]` で足す。embedされるのはresource **request**（lazy）で評価はrendering時 — `addQuery()` は引数の追加、`withQuery()` は置換で、いずれもrender前に呼ぶ。`src` にURI template（`/author{?id}` 等）を使うと **request method引数**が束縛される（`#[Link]` の `$body` 束縛と異なる）。
- **Do not:** embed relに `go*` 名を使わない。embed slotを `$this->body = [...]` で上書きしない。
- **マスター確認（After）:**
  - [ ] `#[Embed]` を持つResourceが scalar を `+=` で足し、embed slot を `=` で潰していない。
  - [ ] embed rel が `go*` でない（Taxonomy名詞）。
  - [ ] `_embedded` に子Resourceが現れることを `HalEnvelopeContractTest.php` 相当で green。



### `auth-oauth-flow`

**OAuth認証フローをResourceで示す**

- **ID:** `auth-oauth-flow`
- **Aliases:** OAuth, OAuth2, Auth0, Google login, AuthInterface, authorization URL, token exchange, league/oauth2-client, 認証, ソーシャルログイン
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** OAuth provider（Google, Auth0）を使ったログインフローをResourceで実装したい。
- **着手前チェック（Before）:**
  - [ ] 認証backendを `AuthInterface` で抽象化し、providerをDI bindingで切り替えられるようにしたか。
  - [ ] GETでauthorization URLを返し、POSTでcode+stateをtoken exchangeする2段階フローにしたか。
  - [ ] 認証失敗時はprovider内部情報を漏らさず401にすると決めたか。
- **Source:**
  - `src/Resource/App/Auth.php`
  - `src/Auth/AuthInterface.php`
  - `src/Auth/GoogleAuthProvider.php`
  - `src/Auth/Auth0AuthProvider.php`
  - `src/Input/AuthExchangeInput.php`
- **Tests:**
  - `tests/Resource/App/AuthTest.php`
  - `tests/Fake/FakeAuthProvider.php`
  - `tests/Smoke/GoogleAuthProviderSmokeTest.php`
- **Key points:** `AuthInterface` でproviderを抽象化。GET→authorization URL、POST→token exchange。失敗は401でprovider内部を漏らさない。providerの切替は2層 — prodは `CMS_AUTH_PROVIDER` envでGoogle/Auth0を選択（不正値は `InvalidAuthProviderException`）、test/fakeは `FakeModule` が `FakeAuthProvider` へbind。session確立まで含むPage側のフローは `admin-session-login` を参照。
- **Do not:** provider固有の例外や内部メッセージをresponse bodyに含めない。
- **マスター確認（After）:**
  - [ ] `AuthInterface` binding が test と prod で切り替わる（FakeAuthProvider vs GoogleAuthProvider）。
  - [ ] GET で authorizationUrl が返り、POST で authenticated user が返ることを `AuthTest.php` 相当で green。

### `file-upload-input`

**`#[InputFile]`でファイルアップロードを受ける**

- **ID:** `file-upload-input`
- **Aliases:** file upload, InputFile, FileUpload, media upload, MIME validation, binary upload, multipart/form-data, Koriym\FileUpload, ファイルアップロード, 画像アップロード
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** HTTP multipartアップロードでファイルを受け取り、検証して保存したい。
- **着手前チェック（Before）:**
  - [ ] `#[InputFile(maxSize: ..., allowedTypes: [...], allowedExtensions: [...])]` で検証を宣言し、違反は `ErrorFileUpload` として引数に届く（Resourceが400へ変換する）形にしたか。
  - [ ] Resource側の再検証（空ファイル/サイズ/MIME/拡張子）は防御の二重化として置くと決めたか。
  - [ ] upload失敗時はロールバック（保存ファイル削除）し、500 または 400 を返すと決めたか。
- **Source:**
  - `src/Resource/App/MediaUpload.php`
- **Tests:**
  - `tests/Resource/App/MediaUploadTest.php`
- **Key points:** `#[InputFile]` で `FileUpload|ErrorFileUpload` を受け、検証後 `move()` で保存→メタデータ登録。失敗時はロールバック。保存名は `bin2hex(random_bytes(8))` + sanitize済みbasenameで衝突・path traversal・上書きを防ぐ。テストはHTTPを起こさず `FileUpload::fromFile()` / `new ErrorFileUpload(...)` をresource paramに直接渡す。
- **Do not:** 検証前にファイルを保存しない。メタデータ登録失敗時に保存ファイルを残さない。`image/svg+xml` を安易に `allowedTypes` に入れない（SVGはscriptを内包できる。本リポジトリはSVG uploadの400拒否をテストで固定している）。
- **マスター確認（After）:**
  - [ ] 不正MIME / 超過サイズ / 空ファイル が 400 で拒否される。
  - [ ] 正常アップロードで 201 + Location が返ることを `MediaUploadTest.php` 相当で green。

### `crawl-data-loader`

**`#[Link(crawl:...)]` + DataLoaderでN+1を解消する**

- **ID:** `crawl-data-loader`
- **Aliases:** crawl, linkCrawl, DataLoader, DataLoaderInterface, N+1, batch query, resource graph, クロール, リソースグラフ, バッチクエリ, N+1解消
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_link.html
- **Use when:** `#[Link(crawl:...)]`でリソースグラフを構築し、子リソースのN+1クエリをバッチで解消したい。
- **着手前チェック（Before）:**
  - [ ] `#[Link(crawl: ...)]` でcrawl名を指定し、リソースグラフを宣言的に構築すると決めたか。
  - [ ] N+1が起きる子リソースに `dataLoader: DataLoaderClass::class` を指定し、`DataLoaderInterface::__invoke()` でバッチクエリを実装すると決めたか。
  - [ ] DataLoaderはBeta（`bear/resource:1.x-dev`）である前提を確認したか。
- **Source:**
  - `src/Resource/App/Crawl/Author.php`
  - `src/Resource/App/Crawl/Articles.php`
  - `src/Resource/App/Crawl/Tags.php`
  - `src/DataLoader/ArticleTagsDataLoader.php`
  - `src/Query/TagQueryInterface.php::listByArticles()`
- **Tests:**
  - `tests/Resource/App/Crawl/CrawlDataLoaderTest.php`
- **Key points:** `#[Link(crawl: ...)]` でグラフ名を宣言。`DataLoaderInterface::__invoke(array $queries): array` でバッチクエリ。keyはURI templateから自動推論。clientからは `ResourceInterface::crawl($uri, $crawlName, $query)` またはfluent DSLの `linkCrawl($rel)` で実行する。
- **Do not:** DataLoaderを使わずに1件ずつクエリするN+1状態を放置しない。DataLoaderが返す行からkey column（この例では `articleId`）を落とさない（分配keyが無い行は例外になる）。
- **マスター確認（After）:**
  - [ ] batch SQL（`tag_list_by_articles` 相当）が1回、per-item SQLが0回になることをquery logで `CrawlDataLoaderTest.php` 相当で green。

### `state-transition-resource`

**状態遷移を独立Resourceとして切り出す**

- **ID:** `state-transition-resource`
- **Aliases:** state machine, state transition, draft published, ArticlePublish, 409 Conflict, AffectedRows, 状態遷移, 公開, 下書きから公開, 競合検出
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** リソースの状態遷移（draft→published等）をフィールド編集(PUT)とは別のResourceとして切り出したい。
- **着手前チェック（Before）:**
  - [ ] フィールド編集と状態遷移を別Resourceに分け、遷移専用のURIを持たせると決めたか。
  - [ ] 既に目標状態にある場合は409 Conflictを返し、再実行を安全にすると決めたか。
  - [ ] `AffectedRows` で原子性を判定し、競合を検出すると理解したか。
- **Source:**
  - `src/Resource/App/ArticlePublish.php`
  - `src/Query/ArticleCommandInterface.php::publish()`
  - `var/db/sql/article_publish.sql`
  - `var/json_validate/article_publish.json`
- **Tests:**
  - `tests/Resource/App/ArticlePublishTest.php`
- **Key points:** 状態遷移は独立Resource（`ArticlePublish`）。原子性の本体はSQL guard — `article_publish.sql` は `WHERE id = :id AND status = 'draft'` でcheck-then-actの隙をDB側で閉じる。`isAffected()` falseは並行publish/deleteを意味し、再読して409/404に振り分ける。`publishedAt` 省略時は現在UTC、明示ISO-8601指定でbackdate可（応答はRFC3339 UTCに正規化）。
- **Do not:** フィールド編集のPUTに状態遷移を混ぜない。既に目標状態の再遷移を200で成功扱いしない。
- **マスター確認（After）:**
  - [ ] draft→published で 200 + publishedAt が返る。
  - [ ] 既に published の再publishで 409、未存在idで 404 が返ることを `ArticlePublishTest.php` 相当で green。

### `error-status-mapping`

**例外→HTTPステータスマッピングとエラーハンドリング**

- **ID:** `error-status-mapping`
- **Aliases:** error handling, exception handler, status mapping, error page, JsonSchemaRequestExceptionHandler, AppThrowableHandler, 例外処理, エラーハンドリング, ステータスマッピング, 例外→HTTP
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** 例外をHTTPステータスコードにマッピングし、カスタムエラーページとJSON Schema検証例外ハンドリングを提供したい。
- **着手前チェック（Before）:**
  - [ ] ドメイン例外を `ExceptionStatusMapper` でHTTPステータスにマッピングすると決めたか。
  - [ ] APIとHTMLで別のハンドラ（`AppThrowableHandler` / `HtmlThrowableHandler`）を使うと理解したか。
  - [ ] JSON Schema validationエラーを `JsonSchemaRequestExceptionHandler` で `ValidationException` に変換すると決めたか。
- **Source:**
  - `src/Provide/Error/ExceptionStatusMapper.php`
  - `src/Provide/Error/AppThrowableHandler.php`
  - `src/Provide/Error/HtmlThrowableHandler.php`
  - `src/Provide/Error/AppErrorPage.php`
  - `src/Module/AppErrorModule.php`
  - `src/Validation/JsonSchemaRequestExceptionHandler.php`
- **Tests:**
  - `tests/Provide/Error/ExceptionStatusMapperTest.php`
  - `tests/Provide/Error/ThrowableHandlerTest.php`
  - `tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`
- **Key points:** `ExceptionStatusMapper` でドメイン例外→HTTPステータス。APIは `AppThrowableHandler`、HTMLは `HtmlThrowableHandler`（`HtmlModule` が差し替え）。mapperが `null` を返す未知のthrowableはframeworkの `ErrorInterface` にdelegateする。`JsonSchemaResponseException`（response schema違反=server bug）は意図的にunmappedのまま500。公式manualも本構成（AppThrowableHandler / HtmlThrowableHandler / 共有ExceptionStatusMapper）をJSON vs HTML contextの参照実装として挙げている。
- **Do not:** ドメイン例外をそのままthrowして框架に500を任せない。
- **マスター確認（After）:**
  - [ ] 各ドメイン例外が正しいステータスコードにマッピングされることを `ExceptionStatusMapperTest.php` 相当で green。
  - [ ] response schema違反がclient errorに化けず500のままであることを pin。

## HTML / Page

### `page-resource-qiq-detail`

**Page Resourceで1件詳細HTMLを描画する**

- **ID:** `page-resource-qiq-detail`
- **Aliases:** Page Resource, Qiq, HTML detail, template variables, article page, setLayout, setBlock, html context, HTML描画, 詳細ページ, Qiqテンプレート
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/html-qiq.html
- **Use when:** App Resourceとは別に、HTML表示用のPage Resourceを作りたい。
- **着手前チェック（Before）:**
  - [ ] App Resource（API）とは別にPage Resource（HTML）を分けると決めたか。
  - [ ] 表示に必要な値はPage Resourceで body に置き、Qiq template は描画に集中させると理解したか。
- **Source:**
  - `src/Resource/Page/Article.php::onGet()`
  - `templates/Page/Article.php`
  - `src/Renderer/CmsQiqRenderer.php`
  - `src/Module/HtmlModule.php`
- **Tests:**
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** Page Resourceが表示に必要なEntityや値をbodyに置く。Qiq templateは描画に集中する。template名はResourceクラスのファイルパスから導出（`src/Resource/Page/Article.php` → `templates/Page/Article.php`）。Qiqは暗黙エスケープしないため出力は `{{h }}` で明示エスケープし、layoutは `setLayout()` + `setBlock()` で組む。公式QiqModule標準ではbodyがtemplateに `$this` として渡るのに対し、`CmsQiqRenderer` は個別変数（`$article` 等）として展開する — 転植先のrendererを先に確認する。
- **Do not:** templateからQuery Interfaceを呼ばない。
- **マスター確認（After）:**
  - [ ] template に Query Interface / DB 呼び出しが無い（`grep -i query templates/Page/<Name>.php` が空）。
  - [ ] Page Resource が描画に必要な値を body へ用意している。
  - [ ] XSSエスケープ（`{{h }}` 漏れ）をfield値のescape検証で pin。
  - [ ] `Resource/Page/ArticleTest.php` 相当でHTML描画と200を green。

### `page-resource-list`

**Page Resourceで一覧HTMLを描画する**

- **ID:** `page-resource-list`
- **Aliases:** HTML list, page list, Qiq list, pager HTML, filter page, 一覧ページ, ページング, 絞り込み, published限定
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/html-qiq.html
- **Use when:** 絞り込みやpager付きの一覧HTMLを表示したい。
- **着手前チェック（Before）:**
  - [ ] list query・filter状態・pager表示用の値を Page Resource 側で準備すると決めたか。
  - [ ] template側でDB fetchやfilter解決をしないと理解したか。
  - [ ] `page` / `perPage` の範囲外入力をResource側でclampすると決めたか。
- **Source:**
  - `src/Resource/Page/ArticleList.php::onGet()`
  - `templates/Page/ArticleList.php`
  - `src/Resource/Page/CategoryList.php::onGet()`
  - `src/Resource/Page/TagList.php::onGet()`
- **Tests:**
  - `tests/Resource/Page/ArticleListTest.php`
  - `tests/Resource/Page/CategoryListTest.php`
  - `tests/Resource/Page/TagListTest.php`
- **Key points:** list query、filter状態、pager表示用値をPage Resourceで準備する。public一覧はuser入力の `status` を無視してserver側でpublishedを強制する（`ArticleList::onGet()` は常に `status: ArticleStatus::Published->value` で `list()` を呼ぶ）。pager表示値は `Page` の `current` / `maxPerPage` / `hasNext` から作る。`CategoryList` / `TagList` はfilter/pagerなしの最小形（一覧Kataの下限例）。
- **Do not:** template側でDB fetchや複雑なfilter解決を行わない。
- **マスター確認（After）:**
  - [ ] template に DB fetch / filter解決ロジックが無い。
  - [ ] public一覧は published のみに制限され、status filter入力でdraftを露出できないことを `ArticleListTest.php` 相当で green。
  - [ ] filter適用の一覧を `ArticleListTest.php` 相当で green。

### `markdown-to-html`

**Markdown本文をHTMLへ変換する**

- **ID:** `markdown-to-html`
- **Aliases:** Markdown, CommonMark, bodyHtml, renderer service, HTML conversion, League CommonMark, html_input ESCAPE, allow_unsafe_links, マークダウン
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/html.html
- **Use when:** EntityのMarkdown本文をHTML templateに渡す前に変換したい。
- **着手前チェック（Before）:**
  - [ ] 変換を interface（`MarkdownRendererInterface`）越しにDI注入すると決めたか。
  - [ ] 変換結果（`bodyHtml`）は Page Resource で作り、template内でparserを生成しないと理解したか。
  - [ ] raw出力（`{{= $bodyHtml }}`）の前提となる変換時の無害化（生HTMLエスケープ・unsafe link拒否）を構成すると決めたか。
- **Source:**
  - `src/Resource/Page/Article.php::onGet()`
  - `src/Service/MarkdownRendererInterface.php`
  - `src/Service/CommonMarkRenderer.php`
  - `src/Provider/CommonMarkConverterProvider.php`
  - `src/Module/AppModule.php`
- **Tests:**
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** 変換サービスはDIで注入し、Page Resourceで `bodyHtml` を作る。Converterは `html_input: HtmlFilter::ESCAPE` + `allow_unsafe_links: false` で構成し、Markdown内の生HTMLと `javascript:` linkを無害化する — templateのraw出力はこの安全化が前提。`CommonMarkConverter` は `toInstance()` でなく `toProvider()` でbindする（prod contextのDI script compile時にClosure内包instanceはserializeできない）。
- **Do not:** template内でMarkdown parserを生成しない。無害化構成なしのConverter出力をrawで出さない。
- **マスター確認（After）:**
  - [ ] 変換が interface 経由でDI注入され、template に `new` parser が無い。
  - [ ] Markdown内の生HTML・unsafe linkが無害化されることを pin。
  - [ ] `bodyHtml` がResource側で生成され、`ArticleTest.php` 相当で変換結果を green。

### `admin-prg-form`

**Admin formでPRGを使う**

- **ID:** `admin-prg-form`
- **Aliases:** admin form, PRG, Post Redirect Get, form validation, author scoped admin, write UI, フォーム, 管理画面, 303 See Other, バリデーション再描画, 422
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/form.html
- **Use when:** HTML formからApp Resourceのwrite APIを呼び、成功時にredirectしたい。
- **着手前チェック（Before）:**
  - [ ] Page Admin が write rules を再実装せず、App Resource の write API を**包む**だけにすると決めたか。
  - [ ] 成功時は303 redirect（Post/Redirect/Get）、validation失敗時はredirectせず422で同じformを `errors` 付き再描画にすると決めたか。
  - [ ] 認可境界（author-scoped）とCSRF保護の前提を理解したか。
- **Source:**
  - `src/Resource/Page/Admin/Article.php`
  - `src/Resource/Page/Admin/ArticleDelete.php`
  - `templates/Page/Admin/Article.php`
  - `templates/Page/Admin/ArticleDelete.php`
- **Tests:**
  - `tests/Resource/Page/Admin/ArticleTest.php`
  - `tests/Resource/Page/Admin/ArticleDeleteTest.php`
  - `tests/Resource/Page/Admin/AuthBoundaryTest.php`
- **Key points:** Page Adminは `$this->resource->post/put/delete('app://self/article', ...)` でApp Resourceを包み、成功時は303 redirect。HTML formはGET/POSTのみなのでeditも `onPost` で受け、`id` の有無でcreate（post）/update（put）を分岐する。validation失敗はPage側で `ValidationException` / `ParameterException` をcatchし、422で `errors` 付きにform再描画（PRGは成功時のみ）。公式manualのform.html（Ray.WebFormModule方式）は別アプローチ — `form-validation-webform` 参照。
- **Do not:** Page AdminとApp Resourceのwrite rulesを別々に二重実装しない。
- **マスター確認（After）:**
  - [ ] Page Admin が `app://self/...` の write を呼び、独自のSQL/write logicを持たない。
  - [ ] 成功時に303 redirect、validation失敗時に422 + errors再描画している。
  - [ ] 他authorの記事をedit/update/deleteできない（403）ことを `ArticleTest.php` / `ArticleDeleteTest.php` の該当ケース相当で green（未ログイン→401の境界は `AuthBoundaryTest.php`）。



### `admin-auth-boundary`

**型で表現する認証境界とauthor-scoped認可**

- **ID:** `admin-auth-boundary`
- **Aliases:** AdminGuard, auth boundary, author-scoped, authorization, session identity, admin page protection, Visitor, AdminUser, UnauthenticatedException, 認可, 認証境界, 401, 403
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** Admin Page Resourceで、ログイン済みユーザーが自分の記事のみ操作できる認可境界を設けたい。
- **着手前チェック（Before）:**
  - [ ] 認証境界を型で表現すると決めたか — sessionが返す `UserInterface` は `Visitor` か `AdminUser`。`AdminGuard` は `UserInterface` を注入し `instanceof AdminUserInterface` で絞り込む（失敗は `UnauthenticatedException` → 401）。
  - [ ] author-scoped認可（作者本人以外の操作）は各Admin Resourceの `owns()` で `authorId` を比較し403にすると決めたか。
- **Source:**
  - `src/Auth/AdminGuard.php`
  - `src/Auth/AdminUserInterface.php`
  - `src/Auth/UserInterface.php`
  - `src/Auth/Visitor.php`
  - `src/Provider/AdminUserProvider.php`
  - `src/Provider/CurrentUserProvider.php`
  - `src/Resource/Page/Admin/Article.php::owns()`
- **Tests:**
  - `tests/Resource/Page/Admin/AuthBoundaryTest.php`
  - `tests/Resource/Page/Admin/ArticleTest.php`
  - `tests/Resource/Page/Admin/ArticleDeleteTest.php`
- **Key points:** 認証境界は型で表現する — `AdminGuard::user()` が `AdminUserInterface` へ絞り込み、非adminは `UnauthenticatedException`（`ExceptionStatusMapper` で401）。author-scoped認可は各Admin Resourceの `owns()`（`$article->authorId === $admin->authorId()`）が担い403を返す。guardに一元化されるのは「admin型への絞り込み」であり、ownership判定は意図的に各Resource本体に置く。
- **Do not:** 認証（401）と認可（403）を混同しない。`AdminUserInterface` を直接bindせず、`UserInterface` からの絞り込みで受ける。
- **マスター確認（After）:**
  - [ ] Visitorがadminページに到達すると401になることを `AuthBoundaryTest.php` 相当で green。
  - [ ] 他authorの記事のedit/update/deleteが403になることを `ArticleTest.php` / `ArticleDeleteTest.php` 相当で green。

### `admin-session-login`

**セッションOAuthログインフロー（login → callback → logout）**

- **ID:** `admin-session-login`
- **Aliases:** session login, OAuth callback, login flow, state parameter, identity mapping, AuthSessionInterface, AuthorIdentityResolver, ログイン, セッション認証, コールバック
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** OAuth providerでログインし、session-backedな管理画面ユーザーを確立したい。
- **着手前チェック（Before）:**
  - [ ] login（state発行→302）/ callback（state検証→token交換→session確立）/ logout（session破棄）を別Page Resourceに分けると決めたか。
  - [ ] CSRF/replay対策として `issueState()` → `consumeState()`（one-time消費 + `hash_equals` 比較）のstate検証を入れると決めたか。
  - [ ] 外部identityを `(provider, subject) → authorId` でマッピングし、emailは初回ログインのfallbackのみと理解したか。
  - [ ] logoutはGETでなくPOST（`#[SameOrigin]` + `#[CsrfToken]`）にすると決めたか。
- **Source:**
  - `src/Resource/Page/Admin/Login.php`
  - `src/Resource/Page/Admin/Callback.php`
  - `src/Resource/Page/Admin/Logout.php`
  - `src/Auth/NativeAuthSession.php`
  - `src/Auth/AuthorIdentityResolver.php`
- **Tests:**
  - `tests/Resource/Page/Admin/LoginTest.php`
  - `tests/Service/AuthorIdentityResolverTest.php`
- **Key points:** Loginは `issueState()` → 302（authorization URL）。Callbackはstate検証失敗/認証失敗で401（provider内部を漏らさない）、author未解決は403、成功で `session->login()` → 303 `/admin/index`。identity mappingは初回のみ `auth_identity_add` され、同一subjectの再ログインで重複作成しない。LogoutはGET=405、POST=303。
- **Do not:** stateを検証せずにcode交換しない。emailを恒久的なアカウントキーにしない。logoutをGETで受けない。
- **マスター確認（After）:**
  - [ ] login→callbackの正常系で303が返り、admin pageが200になる。
  - [ ] 同一subjectの再ログインでidentity mappingが重複作成されないことを `LoginTest.php` 相当で green。

### `admin-confirm-page`

**確認画面Page Resourceで状態遷移をラップする**

- **ID:** `admin-confirm-page`
- **Aliases:** confirm page, preview page, publish confirmation, two-step form, 409 handling, 確認画面, プレビュー, 公開確認
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** 確認ステップ（公開前プレビュー等）を挟んでからApp Resourceの状態遷移を実行したい。
- **着手前チェック（Before）:**
  - [ ] 確認画面を編集フォームのquery-string modeではなく独立Page Resourceにする（URLが状態: `/admin/article/confirm?id=N` がbookmarkable）と決めたか。
  - [ ] POSTはwrite rulesを再実装せず `app://self/article-publish` に転送すると決めたか（Reachability）。
  - [ ] GET/POST間の競合（既にpublished）を409で受け、確認画面に差し戻す設計を理解したか。
- **Source:**
  - `src/Resource/Page/Admin/ArticleConfirm.php`
  - `templates/Page/Admin/ArticleConfirm.php`
  - `src/Resource/App/ArticlePublish.php`
- **Tests:**
  - `tests/Resource/Page/Admin/ArticleConfirmTest.php`
- **Key points:** GET=read-onlyプレビュー（author/category/tag summary付き。既にpublishedならform非表示でnotice）。POSTは `#[SameOrigin]` + `#[CsrfToken]` でApp状態遷移を転送。409はエラーとして差し戻し（`alreadyPublished` + `errors`）、成功は公開記事へ303。author-scoped認可（`owns()`）はGET/POST両方で実施。
- **Do not:** 確認画面に独自のpublish SQLを持たせない。409を成功扱い・500扱いにしない。
- **マスター確認（After）:**
  - [ ] draft GETでフォーム表示、published GETでフォーム非表示（notice表示）。
  - [ ] POST成功で公開記事へ303、既publishedのPOSTで409 + プレビュー差し戻しを `ArticleConfirmTest.php` 相当で green。

## Runtime / representation

### `stream-response`

**ファイルやバイナリをストリームで返す**

- **ID:** `stream-response`
- **Aliases:** streaming, stream response, file download, binary response, BEAR.Streamer, `StreamTransferInject`, `Content-Disposition`, StreamResponder, ストリーミング, ファイルダウンロード, 大容量レスポンス
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/stream.html
- **Use when:** JSONではなく、ファイル本体や大きなレスポンスを返したい。
- **着手前チェック（Before）:**
  - [ ] 通常のMedia応答はJSON形（`src/Resource/App/Media.php`）が正規で、streamにするのは表現がファイル本体そのものである時のみと確認したか。
  - [ ] `StreamTransferInject` を使い、open stream resource を `$this->body` に置くと決めたか。
  - [ ] `Content-Type` / `Content-Length` / `Content-Disposition` を明示すると決めたか。
  - [ ] stream success body には `#[JsonSchema]` を付けないと理解したか。
- **Source:**
  - `src/Resource/App/Variations/MediaStream.php::onGet()`
  - `src/Resource/App/Media.php`
  - `src/Query/MediaQueryInterface.php::item()`
  - `src/Entity/Media.php`
  - `var/media/media-005.svg`
- **Tests:**
  - `tests/Resource/App/Variations/MediaStreamTest.php`
- **Key points:** `StreamTransferInject` を使い、`Content-Type`, `Content-Length`, `Content-Disposition` を明示し、open stream resourceを `$this->body` に置く。body全体をstreamにする必要はなく、bodyの一部にstreamを混ぜると既存rendererと共存できる（manual「With Renderers」）。404分岐ではstreamを開かずJSON error bodyへ戻し、Length/Dispositionヘッダはセットしない。
- **Do not:** success stream bodyに `#[JsonSchema]` を付けない。ファイルopenをtemplateに置かない。
- **マスター確認（After）:**
  - [ ] Resource が `StreamTransferInject` を使い、body が stream resource。
  - [ ] 3つのheader（Type/Length/Disposition）がセットされ、404では不在。
  - [ ] `transfer()` 経由の出力がファイルのバイト列と一致することを `MediaStreamTest.php` 相当で green。

### `cacheable-leaf`

**`#[Cacheable]` だけのleaf resourceを作る**

- **ID:** `cacheable-leaf`
- **Aliases:** cache leaf, `#[Cacheable]`, self URI tag, auto purge, QueryRepository cache, RefreshSameCommand, キャッシュ, 自動無効化, タグベース無効化
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** 単体ResourceのGET/PUTで、利用者コードなしにキャッシュと同一URI purgeを示したい。
- **着手前チェック（Before）:**
  - [ ] cache surface を class-level `#[Cacheable]` だけで表し、manual cache primitive をResourceに出さないと決めたか。
  - [ ] 非prod contextのcache adapterはNullAdapterのため、demo/testでは `Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule())`（ArrayAdapter override）を重ねると理解したか。
- **Source:**
  - `src/Resource/App/Cache/Author.php`
  - `src/Resource/App/Cache/Tag.php`
  - `src/Module/CacheShowcaseModule.php`
- **Tests:**
  - `tests/Resource/App/Cache/AuthorCacheTest.php`
- **Key points:** class-level `#[Cacheable]` がcache surface。manual cache primitiveはResourceに出さない。デフォルトはevent-driven無効化（TTL無期限）で、TTLが必要なら `#[Cacheable(expirySecond: 30)]` / `#[Cacheable(expiryAt: 'expiry_at')]` を指定できる。
- **Do not:** leaf resourceに不要な `Surrogate-Key` 手書きコードを足さない。
- **マスター確認（After）:**
  - [ ] class に `#[Cacheable]` があり、sourceに `Surrogate-Key` / `UriTagInterface` / `fromAssoc` 等のcache primitiveが現れない（reflectionで pin）。
  - [ ] repeated GET が同一 `ETag` を返し、PUT後にGETが更新され同一URIがpurgeされることを `AuthorCacheTest.php` 相当で green。

### `cache-embed-dependency`

**`#[Embed]` 親Resourceの依存を自動合成する**

- **ID:** `cache-embed-dependency`
- **Aliases:** cache parent, embed dependency, auto dependency, ETag dependency, AuthorProfile, キャッシュ依存, 依存解決, タグベース無効化
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** 親Resourceが子Resourceをembedし、子の更新で親cacheも無効化したい。
- **着手前チェック（Before）:**
  - [ ] 単一の子依存は `#[Embed]` で表し、`fromAssoc()` を使わないと決めたか。
  - [ ] 親はmanual cache codeを持たず、QueryRepositoryが子URI tagを親へmergeする前提を理解したか。
  - [ ] demo/testでは `CacheShowcaseModule`（ArrayAdapter override）を重ねると理解したか。
- **Source:**
  - `src/Resource/App/Cache/AuthorProfile.php`
  - `src/Resource/App/Cache/Author.php`
  - `src/Module/CacheShowcaseModule.php`
- **Tests:**
  - `tests/Resource/App/Cache/AuthorProfileCacheTest.php`
- **Key points:** `#[Embed]` 子のURI tagをQueryRepositoryが親へmergeする。親Resourceはmanual cache codeを持たない。子が見つからない時は `$this->body` を丸ごと置き換えてEmbed Requestを落として404を返す — `CacheInterceptor` はcode 200のみ保存するため404がstale cacheにならない。
- **Do not:** `#[Embed]` で表せる単一子依存に `fromAssoc()` を使わない。
- **マスター確認（After）:**
  - [ ] 親Resourceが `#[Embed]` で子を持ち、cache依存の手書きコードが無い。
  - [ ] 親レスポンスの `Surrogate-Key` に子URI tagが含まれる。
  - [ ] 子の更新で親cacheが無効化されることを `AuthorProfileCacheTest.php` 相当で green。

### `cache-body-derived-dependency`

**body由来の可変長依存を `fromAssoc()` で宣言する**

- **ID:** `cache-body-derived-dependency`
- **Aliases:** variable dependencies, body-derived dependency, `UriTagInterface::fromAssoc`, surrogate key, article tags cache, サロゲートキー, 可変長依存, タグ無効化
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** DB結果から得たN個の子URIに依存するResourceをcacheしたい。
- **着手前チェック（Before）:**
  - [ ] 依存先が**可変長**（N個）で静的 `#[Embed]` では表せないことを確認したか。
  - [ ] 唯一のmanual cache primitiveを `UriTagInterface::fromAssoc(...)` に限定すると決めたか。
  - [ ] demo/testでは `CacheShowcaseModule`（ArrayAdapter override）を重ねると理解したか。
- **Source:**
  - `src/Resource/App/Cache/ArticleTags.php`
  - `src/Resource/App/Cache/Tag.php`
  - `src/Module/CacheShowcaseModule.php`
- **Tests:**
  - `tests/Resource/App/Cache/ArticleTagsCacheTest.php`
- **Key points:** `UriTagInterface::fromAssoc('app://self/cache/tag{?id}', $items)` が唯一のmanual cache primitive。戻り値は `$this->headers[Header::SURROGATE_KEY]` に代入する（複数tagはspace区切り。単一URI依存なら `($this->uriTag)(new Uri(...))` でもよい）。
- **Do not:** 可変長依存を静的 `#[Embed]` で無理に表現しない。空tag headerをセットしない。main `app://self/article` へのwriteがこのshowcase cacheをpurgeすると誤解しない（意図的スコープ外 — 本番で同依存が要る場合はwrite側に明示的無効化を足す）。
- **マスター確認（After）:**
  - [ ] 依存宣言が `fromAssoc()` 1箇所に集約され、空の時はheaderをセットしない。
  - [ ] 子tagの1つを更新すると当該親cacheだけが無効化され、無関係な子tagの更新では無効化されないことを `ArticleTagsCacheTest.php` 相当で green。

### `async-embed-parallel`

**embed graphを並列実行に載せる**

- **ID:** `async-embed-parallel`
- **Aliases:** async, parallel embed, BEAR.Async, ext-parallel, Swoole, embedded resources, 並列実行, 非同期, 並列化
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/async.html
- **Use when:** 既存Resourceの `#[Embed]` graphを変更せずに、runtime overlayで並列化したい。
- **着手前チェック（Before）:**
  - [ ] Resource code は通常の `#[Embed]` のまま変えず、並列化は runtime/context 側のmodule overlayで行うと理解したか。
  - [ ] 実行に ext-parallel + ZTS PHP（または Swoole）が必要な前提を確認したか（BEAR.AsyncはmanualでAlpha表記）。
  - [ ] 並列実行される `#[Embed]` 子はread-only（冪等GET）で順序依存が無く、thread boundaryを跨ぐ値はcopyable（scalar/null/そのnested array）のみと確認したか。各workerは独立したDI containerを持つ。
- **Source:**
  - `bin/async.php`
  - `src/Resource/App/Article.php::onGet()`
  - `docker-compose.yml`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** Resource codeは通常の `#[Embed]` のまま。runtime/context側でparallel moduleを重ねる。実行は `composer parallel:up`（ZTS + ext-parallel container起動）→ `composer parallel:demo`。Swooleは別entrypointではなく `AsyncSwooleModule` + connection poolをAppModuleにinstallする形（本リポジトリはDockerfileのみ用意、module wiringは未着手）。
- **Do not:** 並列化のためにResource body assemblyを別物に書き換えない。
- **マスター確認（After）:**
  - [ ] sync版と並列版で Resource クラスの差分がゼロ（並列化はcontext側のみ）。
  - [ ] 並列版でも同じHAL envelopeが出ることを `HalEnvelopeContractTest.php` 相当で green。

### `cli-resource`

**ResourceをCLIコマンドとして公開する**

- **ID:** `cli-resource`
- **Aliases:** CLI, `#[Cli]`, `#[Option]`, bear-cli-gen, article-show, article-list, bear/cli, コマンドライン, CLIコマンド化, Homebrew配布
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cli.html
- **Use when:** 同じResource methodをHTTPだけでなくCLIからも呼びたい。
- **着手前チェック（Before）:**
  - [ ] 同じResource methodを再利用し、CLI用の別serviceを重複実装しないと決めたか。
  - [ ] method に `#[Cli]` / `#[Option]` を付け、`composer cli` で生成すると理解したか。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/App/Articles.php::onGet()`
  - `bin/cli/article-show`
  - `bin/cli/article-list`
- **Tests:**
  - なし（専用テストは無い。生成コマンドの動作は手動確認）
- **Key points:** Resource methodに `#[Cli]` と `#[Option]` を付け、`composer cli` で生成する。生成コマンドは `cli-hal-api-app` contextのresource clientを呼ぶだけ（重複実装ゼロ）。default出力は `output:` で指定したbody fieldのみで、`--format json` でAPIと同じfull JSON。errorはstderr、exit codeはHTTP status mapping（0=success / 1=client error / 2=server error）。GitHub repository設定時はHomebrew formulaも生成される。
- **Do not:** CLI用に別のapplication serviceを重複実装しない。
- **マスター確認（After）:**
  - [ ] CLI が既存Resource methodを呼び、ロジックの重複実装が無い。
  - [ ] `bin/cli/article-show --help` がUsage/Optionsを表示し、`bin/cli/article-show -i 1 --format json` が `app://self/article?id=1` のGETと同じbodyを返す。



### `csrf-same-origin-protection`

**CSRFトークン + Same-Origin interceptorをAOPでbindする**

- **ID:** `csrf-same-origin-protection`
- **Aliases:** CSRF, CsrfToken, SameOrigin, interceptor, AOP, form protection, synchronizer token, シンクロナイザートークン, CSRF対策, Ray.Csrf, _csrf_token, Sec-Fetch-Site
- **Status:** `canonical`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** Admin Page Resourceのwrite操作をCSRF攻撃とCross-Site Origin攻撃から保護したい。
- **着手前チェック（Before）:**
  - [ ] `#[CsrfToken]` と `#[SameOrigin]` の2つのAttributeを使い、それぞれ interceptor をAOP bindすると決めたか。
  - [ ] CSRFトークンはsynchronizer token方式（sessionに保存したserver側stateと `hash_equals` で比較。cookieは使わない）と理解したか。
  - [ ] Same-Originは `Sec-Fetch-Site` / `Origin` / `Referer` の3シグナルで判定し、全欠落時はfail-closedにすると理解したか。
  - [ ] `CsrfModule(allowedOrigin)` の `AllowedOrigin` が `null` なら両interceptorとも素通し（dev/CLI/test用スイッチ）で、prodでは `CMS_ALLOWED_ORIGIN` の設定が必要と理解したか。
- **Source:**
  - `src-csrf/Attribute/CsrfToken.php`
  - `src-csrf/Attribute/SameOrigin.php`
  - `src-csrf/Interceptor/CsrfTokenInterceptor.php`
  - `src-csrf/Interceptor/SameOriginInterceptor.php`
  - `src-csrf/CsrfModule.php`
  - `src-csrf/SessionCsrfToken.php`
  - `src/Module/AppModule.php`
- **Tests:**
  - `tests/Interceptor/CsrfTokenInterceptorTest.php`
  - `tests/Interceptor/CsrfTokenWiringTest.php`
  - `tests/Interceptor/SameOriginInterceptorTest.php`
  - `tests/Interceptor/SameOriginWiringTest.php`
  - `tests/Interceptor/AdminPageCsrfAttributeCoverageTest.php`
- **Key points:** `#[CsrfToken]` → synchronizer token検証（`$_SESSION` 保存 + `hash_equals`）。`#[SameOrigin]` → Sec-Fetch-Site/Origin/Referer 3シグナル判定、fail-closed（未知の `Sec-Fetch-Site` 値もfallbackしない）。malformedな `Origin`/`Referer` は400、mismatchは403。hidden fieldはrendererが全templateへ供給する `$csrfTokenField` で埋め、logout時は `CsrfTokenInterface::clear()`。`AdminPageCsrfAttributeCoverageTest` が全Admin write methodへの付け忘れをreflectionで検出する。
- **Do not:** シグナル全欠落時に許可しない（fail-closed）。CSRFトークンをURLに露出させない。prodで `CMS_ALLOWED_ORIGIN` 未設定のまま公開しない。
- **マスター確認（After）:**
  - [ ] token mismatch で `ForbiddenException` が throw される。
  - [ ] origin mismatch で `ForbiddenException` が throw される。
  - [ ] 全Admin write methodにattributeが付いていることを coverage test 相当で green。
  - [ ] `CsrfTokenInterceptorTest.php` / `SameOriginInterceptorTest.php` 相当で green。

### `cache-purge`

**`#[Purge]`でwrite時にcollection cacheを手動無効化する**

- **ID:** `cache-purge`
- **Aliases:** Purge, cache invalidation, manual purge, collection cache, #[Purge], キャッシュ無効化, パージ, 手動無効化
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** write操作（POST/PUT/DELETE）後に、関連collection resourceのキャッシュを手動で無効化したい。
- **着手前チェック（Before）:**
  - [ ] `#[Purge(uri: 'app://self/<collection>')]` をwrite methodに付け、該当collection cacheを無効化すると決めたか。
  - [ ] Purge対象はcollection URI（`articles`, `categories`）で、item URIでないことを確認したか。
  - [ ] purgeされるのは指定したcanonical URIのエントリのみで、query-string variant（`?categoryId=3` 等）は残ると理解したか（`docs/scope.md` D1）。
- **Source:**
  - `src/Resource/App/Article.php::onPost()`
  - `src/Resource/App/Article.php::onPut()`
  - `src/Resource/App/Article.php::onDelete()`
  - `src/Resource/App/Category.php`
- **Tests:**
  - `tests/Resource/App/CacheTest.php`
- **Key points:** `#[Purge(uri: 'app://self/articles')]` をwrite methodに付ける。`#[Purge]` はrepeatableで、URI templateにmethod引数をbindできる（`#[Purge(uri: 'app://self/user/friend?user_id={id}')]`）。非 `#[Cacheable]` クラスでは `#[Purge]`/`#[Refresh]` 付きmethodのみにinterceptorがbindされるため、write methodごとの付け忘れに注意。
- **Do not:** `#[Purge]` にitem URIを渡さない（同一URIの無効化は自動に任せる）。query-string variantまでpurgeされると誤解しない。write後のPurgeを忘れない。
- **マスター確認（After）:**
  - [ ] POST/PUT/DELETE後にRepositoryLoggerのログへ `purge-query-repository` と対象collection URIが出ることを `CacheTest.php` 相当で green。

### `donut-cache`

**`#[DonutCache]`で部分キャッシュを示す**

- **ID:** `donut-cache`
- **Aliases:** DonutCache, donut caching, partial cache, donut hole, ArticlePreview, ドーナツキャッシュ, 部分キャッシュ, ドーナツホール
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** Resource全体のうち、embedされた非キャッシュ可能部分を除いたキャッシュ可能部分を分離してキャッシュしたい。
- **着手前チェック（Before）:**
  - [ ] `#[DonutCache]` はembed子（hole）が非キャッシュ可能な時に使い、全体コンテンツキャッシュの対概念は `#[CacheableResponse]` と理解したか（manual: DonutCacheでは全体はキャッシュされずETagも出ない）。
  - [ ] `#[Cacheable]`（QueryRepositoryの個別Resourceキャッシュ・TTL型）とは別枠の使い分けと理解したか。
- **Source:**
  - `src/Resource/App/Cache/ArticlePreview.php`
- **Tests:**
  - `tests/Resource/App/CacheTest.php`
- **Key points:** `#[DonutCache]` では全体が動的扱いになるため全体キャッシュは作られずETagも出力されない。donut部分の計算は再利用され、holeがcacheableな場合（donut hole cache）は依存解決が自動で行われる。本リポジトリの `ArticlePreview` はscalar-onlyの最小実例（donut-holeのplaceholderはstring-renderer向けのためHALでは示していない）— hole再描画の実挙動はmanualを一次資料とする。
- **Do not:** `#[CacheableResponse]`（全体キャッシュ）と `#[DonutCache]`（部分キャッシュ）を混同しない。
- **マスター確認（After）:**
  - [ ] class に `#[DonutCache]` があり、GETでRepositoryLoggerのログに `try-donut-view` / `put-donut` が出ることを `CacheTest.php` 相当で green。

### `cacheable-response`

**`#[CacheableResponse]`でレスポンス全体をキャッシュする**

- **ID:** `cacheable-response`
- **Aliases:** CacheableResponse, response cache, whole content cache, Articles, Categories, レスポンスキャッシュ, 全体キャッシュ, ETag
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** Collection resourceのレスポンス全体をキャッシュし、ETag付きで配信したい。
- **着手前チェック（Before）:**
  - [ ] `#[CacheableResponse]` は全体コンテンツキャッシュ（embed子も含む）で、`#[Cacheable]` は個別Resourceのキャッシュだと理解したか。
  - [ ] Donut cacheと違い、embed子もキャッシュ対象になることを理解したか。
- **Source:**
  - `src/Resource/App/Articles.php`
  - `src/Resource/App/Categories.php`
- **Tests:**
  - `tests/Resource/App/CacheTest.php`
  - `tests/Resource/App/ArticlesTest.php`
- **Key points:** `#[CacheableResponse]` は全体キャッシュ。embed子も含めてキャッシュされる（本showcaseのArticles/Categories自体にはembed子が無い — embed込みの実例はmanualのBlogPosting例）。TTLは `DonutRepositoryInterface::put($this, ttl:, sMaxAge:)` で指定でき、default TTLはCDN module依存（tag無効化対応CDNなら実質無期限、それ以外は10秒）。ETagによる304応答は `conditional-request-304` が担う。
- **Do not:** 3つのcache属性（Cacheable / DonutCache / CacheableResponse）を混同しない。
- **マスター確認（After）:**
  - [ ] GETでRepositoryLoggerのログに `try-donut-view` / `put-donut` / `save-etag` が出ることを `CacheTest.php` 相当で green。

### `conditional-request-304`

**条件付きリクエスト（If-None-Match → 304）で転送を省く**

- **ID:** `conditional-request-304`
- **Aliases:** conditional request, 304 Not Modified, If-None-Match, ETag revalidation, HttpCacheInterface, isNotModified, 条件付きリクエスト, 再検証
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** クライアントが保持するETagで再検証し、変更が無ければbodyを送らず304で応答したい。
- **着手前チェック（Before）:**
  - [ ] ETag付与は `#[Cacheable]` / `#[CacheableResponse]`（QueryRepository）に任せ、手書きしないと決めたか。
  - [ ] 304判定はResourceではなくbootstrap（routing前）で行うと理解したか。
  - [ ] write時のETag無効化はAOPがキャッシュ無効化と連動して管理することを理解したか。
- **Source:**
  - `src/Bootstrap.php`
  - `vendor/bear/query-repository/src/HttpCache.php` *(external package)*
  - `src/Resource/App/Cache/Author.php`
- **Tests:**
  - `tests/Resource/App/Cache/AuthorCacheTest.php`
  - `tests/Resource/App/Cache/AuthorProfileCacheTest.php`
  - `tests/Resource/App/Cache/ArticleTagsCacheTest.php`
- **Key points:** `HttpCacheInterface::isNotModified($server)` が `If-None-Match` を検査し、hitなら `transfer()` でrouting前に304を返す（`src/Bootstrap.php`）。ETag / Last-ModifiedはQueryRepositoryが自動付与し、writeで自動無効化される。304はbody転送もresource実行も省くため、計算資源とネットワーク資源の両方を節約する。
- **Do not:** ResourceでETag文字列を手計算しない。304判定をrouting後に置かない。
- **マスター確認（After）:**
  - [ ] GET応答に `ETag` / `Last-Modified` が自動付与される。
  - [ ] 同一ETagの `If-None-Match` で `isNotModified()` が true、write後は false になることを `AuthorCacheTest.php` 相当で green。

### `import-app`

**ImportAppModuleで他アプリのResourceを呼ぶ**

- **ID:** `import-app`
- **Aliases:** ImportApp, ImportAppModule, multi-app, composer package, cross-app resource, System Boundary, アプリ間連携, 他アプリ呼び出し, マルチアプリ
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/import.html
- **Use when:** composer installした別アプリのResourceを、自アプリから `app://<host>/...` で呼びたい。
- **着手前チェック（Before）:**
  - [ ] `ImportAppModule` に `ImportApp($host, $namespace, $context)` を渡してinstallすると決めたか。
  - [ ] 呼び出し側は `app://<host>/<resource>` でアクセスし、`#[Embed]` / `#[Link]` も使えると理解したか。
  - [ ] importするappがcomposer autoload可能であることを確認したか（本リポジトリはautoload-devのPSR-4 mapで代替）。
- **Source:**
  - `examples/import/ImportedCatalog/src/Resource/App/Status.php`
  - `examples/import/ImportedCatalog/src/Module/AppModule.php`
  - `tests/Example/ImportAppExampleTest.php`
- **Tests:**
  - `tests/Example/ImportAppExampleTest.php`
- **Key points:** `ImportAppModule` で他アプリをimport。host名経由でresource呼び出し。`#[Embed]` / `#[Link]` も使用可能。マイクロサービス化せずともcomposer経由でアプリ間連携できる。他framework/CMS側からは `BEAR\Package\Injector::getInstance($appName, $context, $appDir)` で対象appのresource clientを直接取得できる。
- **Do not:** リソース共有のためだけにHTTP microserviceを立てない（composer importで足りる）。環境変数はglobalなのでapp間で衝突しないようprefixを付ける。
- **マスター確認（After）:**
  - [ ] 他アプリのresourceが `app://<host>/...` で呼べることを `ImportAppExampleTest.php` 相当で green。



## Event Sourcing

### `event-extraction`

**Semantic Logger観察ログからimmutable Eventを抽出する**

- **ID:** `event-extraction`
- **Aliases:** event sourcing, SemanticLogExtractor, Event, RecordedMethods, semantic logger, event extraction, observation, イベントソーシング, イベント抽出, 観察ログ
- **Status:** `showcase`
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** アプリケーションの状態変化をイベントとして記録し、replay可能なsource of truthにしたい。
- **着手前チェック（Before）:**
  - [ ] Semantic Loggerのopen/close観察ツリーからEventを抽出し、ドメインにevent-dispatchコードを追加しないと理解したか。
  - [ ] `RecordedMethods` で記録対象method（デフォルト: POST/PUT/PATCH/DELETE、GETは除外）を制御すると決めたか。
  - [ ] Eventは `uri`, `method`, `params`, `timestamp`, `result` の事実のみを持つと理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/SemanticLogExtractor.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Event.php` *(external package)*
  - `vendor/bear/event-sourcing/src/RecordedMethods.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Module/EventSourcingModule.php` *(external package)*
  - `tests/Fake/FakeResourceRequestContext.php`
  - `tests/Fake/FakeResourceResponseContext.php`
- **Tests:**
  - `tests/Smoke/EventExtractionTest.php`
- **Key points:** Semantic Loggerが観察源。Eventはresource操作（method on uri）の不変事実。`RecordedMethods` で記録範囲を制御。code >= 400 はスキップ。Eventの `result` はclose contextの `body` フィールドから取る — `resource-observation-bridge` の実contextは `body_ref` を出すため、bridge由来logから抽出したEventの `result` は null になる点に注意。
- **Do not:** ドメインコードにevent-dispatchを追加しない。Eventにドメインロジックを入れない。
- **マスター確認（After）:**
  - [ ] write操作（POST/PUT/PATCH/DELETE）のみがデフォルトで抽出される。
  - [ ] `RecordedMethods::WITH_READS` でGETも含まれる。
  - [ ] code >= 400 の操作はスキップされることを `EventExtractionTest.php` 相当で green。

### `event-filter-replay`

**Eventsコレクションをフィルタしてreplayする**

- **ID:** `event-filter-replay`
- **Aliases:** event replay, filter events, CallbackFilterIterator, Events, replay, projection, イベントリプレイ, 再生, イベントフィルタ
- **Status:** `showcase`
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** 抽出したEventをURI prefix / params / timestampでフィルタし、特定エンティティの状態変化をreplayしたい。
- **着手前チェック（Before）:**
  - [ ] `Events` はcountable + iterableで、PHP標準の `CallbackFilterIterator` でフィルタすると理解したか。
  - [ ] query methodをEventsに追加せず、filterをstackすると理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/Events.php` *(external package)*
  - `vendor/bear/event-sourcing/src/EventsInterface.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Event.php` *(external package)*
- **Tests:**
  - `tests/Smoke/EventReplayTest.php`
- **Key points:** `CallbackFilterIterator` でURI prefix / params / method（timestampも `Event->timestamp` の比較で同手法）をstacked filterする。query methodを生やさずfilterをstack。`EventsInterface` は `IteratorAggregate` なので `getIterator()` を明示的に渡し、filterはkeyを保持するため `iterator_to_array($it, false)` でlist化する。
- **Do not:** Eventsコレクションに専用query methodを追加しない（PHP標準iteratorで十分）。
- **マスター確認（After）:**
  - [ ] 特定id / URI prefix / method でフィルタされたEventが正しく抽出されることを `EventReplayTest.php` 相当で green。

### `event-store-persistence`

**EventStoreInterfaceでEventを永続化する**

- **ID:** `event-store-persistence`
- **Aliases:** EventStore, InMemoryEventStore, MediaQueryEventStore, event persistence, event storage, EventStoreQueryInterface, イベント永続化, イベントストア
- **Status:** `support`
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** 抽出したEventを永続化し、後から全Eventを再取得したい。
- **着手前チェック（Before）:**
  - [ ] `EventStoreInterface` は `append`, `appendAll`, `all` の小さい永続化ポートで、runtime hookではないと理解したか。
  - [ ] test用は `InMemoryEventStore`、SQL永続化は `MediaQueryEventStore`（Ray.MediaQuery経由）を使うと決めたか。
  - [ ] SQL永続化では `event_store_append` / `event_store_list` のSQLファイルとevent_storeテーブルをアプリ側で用意すると理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/EventStoreInterface.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Store/InMemoryEventStore.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Store/MediaQueryEventStore.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Query/EventStoreQueryInterface.php` *(external package)*
- **Tests:**
  - `tests/Smoke/EventStoreTest.php`
- **Key points:** `EventStoreInterface` は小さい永続化ポート。InMemory（test）とMediaQuery（SQL）の2実装。ES ModuleはアプリのDB設定を隠さない（`MediaQueryEventStoreModule` は `EventStoreQueryInterface` の実装をアプリのMediaQueryModuleに委ねる）。`MediaQueryEventStore` はparams/resultをJSONカラムにserializeし、timestampはマイクロ秒付きで保存・復元する。
- **Do not:** runtime中の自動永続化をしない（明示的に `appendAll()` を呼ぶ）。
- **マスター確認（After）:**
  - [ ] InMemoryEventStore に appendAll → all で同じEventが戻ることを `EventStoreTest.php` 相当で green。

### `resource-observation-bridge`

**BEAR.Resource実行からSemantic Logger観察ログを生成する**

- **ID:** `resource-observation-bridge`
- **Aliases:** ResourceObservationModule, InvokerInterface, BodyStoreInterface, FileBodyStore, DevLogModule, observation bridge, SemanticLogInvoker, リソース観察, セマンティックログ
- **Status:** `showcase`
- **Manual:** —（公式マニュアル章なし。パッケージ: https://github.com/bearsunday/BEAR.EventSourcing ）
- **Use when:** BEAR.Resourceの実行ツリーをSemantic Logger観察ログとして記録し、event extractionの入力にしたい。
- **着手前チェック（Before）:**
  - [ ] `ResourceObservationModule` で `InvokerInterface` をdecorateし、`LoggerInterface` はdecorateしないと理解したか。
  - [ ] `BodyStoreInterface` でrendered bodyを外部化し、`body_ref` で参照すると決めたか（未指定時のデフォルトは `NullBodyStore` = body外部化なし）。
  - [ ] 開発時は `DevLogModule` でbodyファイルを自動クリア＋全method記録すると理解したか。
  - [ ] 観察側も `RecordedMethods` で対象を絞る（デフォルトはwrite系のみ。GET観察は `WITH_READS`）と理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/Resource/ResourceObservationModule.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Resource/SemanticLogInvoker.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Resource/BodyStoreInterface.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Resource/NullBodyStore.php` *(external package)*
  - `tests/Fake/Observation/Resource/App/Hello.php`
- **Tests:**
  - `tests/Smoke/ResourceObservationTest.php`
- **Key points:** `InvokerInterface` decorate（`rename` + `toConstructor` で元のInvokerを退避してwrap）で観察ログ生成。`BodyStoreInterface` でbody外部化。`DevLogModule` は開発用（全method記録＋自動クリア）。観察はrequestを壊さない — BodyStore失敗時も実codeを保ち、resource例外時もcloseを記録してrethrowする。bridgeのclose contextは `body` でなく `body_ref` を出すため、抽出Eventの `result` はnullになる（bodyが必要なら `body_ref` のfileを読む）。
- **Do not:** `LoggerInterface` をdecorateしない（`InvokerInterface` が正しいdecorate対象）。
- **マスター確認（After）:**
  - [ ] Resource実行後にSemantic Loggerログが生成され、Event抽出可能になることを `ResourceObservationTest.php` 相当で green。

## Deferred execution

### `defer-resource-request`

**`#[Defer]` + `#[Link]`で応答後に実行するfollow-up Resourceを宣言する**

- **ID:** `defer-resource-request`
- **Aliases:** defer, deferred, #[Defer], 202 Accepted, post-response execution, DeferModule, DeferInterceptor, SyncDefer, 遅延実行, 応答後実行, 非同期follow-up
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/defer.html
- **Use when:** Resourceが202 Acceptedを即時返却し、重いfollow-up処理をレスポンス転送後に実行したい。
- **着手前チェック（Before）:**
  - [ ] `#[Defer(['rel1', 'rel2'])]` で `#[Link]` relを指定し、hardcoded URIを使わないと決めたか。
  - [ ] follow-up Resourceは通常のResourceであり、deferを意識しないと理解したか。
  - [ ] enqueue実行戦略（sync/queue等）は `DeferInterface` binding、SAPI別の接続解放は `ConnectionCloserInterface` binding（Swooleは丸ごと差し替え）で切り替え、Resource codeは変えないと理解したか。
  - [ ] 早期返却が保証されるのはPHP-FPM / LiteSpeedのみで、Apache mod_phpはbest-effort（BEAR.DeferはmanualでAlpha表記）と理解したか。
- **Source:**
  - `vendor/bear/defer/src/Attribute/Defer.php` *(external package)*
  - `vendor/bear/defer/src/DeferInterceptor.php` *(external package)*
  - `vendor/bear/defer/src/Module/DeferModule.php` *(external package)*
  - `vendor/bear/defer/src/SyncDefer.php` *(external package)*
  - `tests/Fake/Defer/Resource/App/Article.php`
  - `tests/Fake/Defer/Resource/App/Publish.php`
  - `tests/Fake/Defer/SpyDefer.php`
- **Tests:**
  - `tests/Resource/App/DeferTest.php`
- **Key points:** `#[Defer]` は `#[Link]` relを参照し、`uri_template($link->href, (array) $ro->body)` でbodyからURIを展開（relが `#[Link]` に無ければ `LinkRelNotFoundException`）。installは既存responder moduleをwrapする形：`$this->install(new DeferModule(new YourHttpResponderModule()))`。`SyncDefer::flush()` はqueueを先にクリアしてから全requestを実行し、失敗を集約して throw する（1件の失敗が残りを止めない）。
- **Do not:** Resource内でdefer callを手書きしない（`#[Defer]` で宣言的）。follow-up URIをhardcodeしない（`#[Link]` 経由）。確実な実行やリトライが必要な処理（課金・在庫・失えない記録）をdeferしない — job queueを使う。
- **マスター確認（After）:**
  - [ ] 202 が即時返却され、`#[Defer]` relがbodyから展開されたresolved requestとしてenqueueされ、`flush()` で実行されることを `DeferTest.php` 相当で green。

### `defer-conditional`

**`DeferInterface::add()`で条件付きdeferを手動制御する**

- **ID:** `defer-conditional`
- **Aliases:** conditional defer, DeferInterface, manual defer, add(), flush(), DeferTransfer, ConnectionCloserInterface, 条件付き遅延実行, 手動enqueue, fastcgi_finish_request
- **Status:** `showcase`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/defer.html
- **Use when:** follow-up処理が条件付きの場合、`#[Defer]` を迂回して `DeferInterface::add()` で手動制御したい。
- **着手前チェック（Before）:**
  - [ ] 常にdeferするなら宣言的な `#[Defer]`（`defer-resource-request`）、runtime条件で分岐する時だけ `add()` を使うと決めたか。
  - [ ] `DeferInterface` と `ResourceInterface` をinjectし、`$defer->add($request)` で手動enqueueすると決めたか。
  - [ ] `DeferTransfer` がbase transfer後にconnectionをreleaseし、その後に `flush()` が走ることを理解したか。
- **Source:**
  - `vendor/bear/defer/src/DeferInterface.php` *(external package)*
  - `vendor/bear/defer/src/DeferTransfer.php` *(external package)*
  - `vendor/bear/defer/src/ConnectionCloserInterface.php` *(external package)*
  - `vendor/bear/defer/src/SapiConnectionCloser.php` *(external package)*
  - `tests/Fake/Defer/Resource/App/ConditionalArticle.php`
  - `tests/Fake/Defer/SpyDefer.php`
- **Tests:**
  - `tests/Resource/App/DeferTest.php`
- **Key points:** `DeferInterface::add(callable $request)` で手動enqueue — BEAR.Resourceの `Request` はinvokableなのでそのまま渡せる。`DeferTransfer` は transfer → connection release → flush の順で、`flush()` はfinallyで実行される（connection close失敗でもdeferred workは走る）。`SapiConnectionCloser` は `fastcgi_finish_request`（PHP-FPM）→ `litespeed_finish_request` → その他はbest-effort `flush()`、CLIはno-op。
- **Do not:** `DeferInterface` のsingleton queueをflushせずに放置しない（`flush()` はrequest boundaryで必須）。
- **マスター確認（After）:**
  - [ ] 条件フラグfalseでenqueueされず、trueで1件enqueueされ `flush()` で実行されることを `DeferTest.php` 相当で green。

## Tests / fake

### `fake-sql-query`

**DBなしでMediaQueryをFakeする**

- **ID:** `fake-sql-query`
- **Aliases:** FakeSqlQuery, no DB test, fake context, test context, hermetic tests, フェイク, テストダブル, DBなしテスト, インメモリDB
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** DBなしでResource、Query、write flowをテストしたい。
- **着手前チェック（Before）:**
  - [ ] Fake は便利データではなく Query contract の実行可能な代替であると理解したか。
  - [ ] `DbQueryInterceptor` の3分岐を理解したか — `#[Pager]` 付きはgetPages/getCount、戻り型が `PostQueryInterface`（`InsertedRow`/`AffectedRows` 等）はexecPostQuery、それ以外を戻り型でgetRow/getRowListに振り分ける（write idもgetRow/getRowList側で扱う）。
- **Source:**
  - `tests/Fake/FakeSqlQuery.php`
  - `tests/Fake/FakePages.php`
  - `tests/Smoke/FakeEntityFetch.php`
  - `tests/Smoke/FakePostQueryRows.php`
  - `src/Module/FakeModule.php`
  - `src/Module/TestModule.php`
  - `var/fake/article.json`
- **Tests:**
  - `tests/Smoke/FakeSqlQueryTest.php`
  - `tests/Smoke/MediaQuerySmokeTest.php`
- **Key points:** default PHPUnitはDBなしで動く。Fakeは便利データではなくQuery contractの実行可能な代替。`FakeSqlQuery` はpublicな `execLog` / `queryLog` を持ち、発行されたsqlIdとvaluesをテストからassertできる（`FakeModule` がsingleton bindするため同一injector内で共有）。`TestModule` は `FakeModule` の上にsession/PDOのtest用overrideを重ねる2段構成。
- **Do not:** すぐmockに逃げず、既存Fakeの意味を保つ。
- **マスター確認（After）:**
  - [ ] 主要テストがDBなしで green（`vendor/bin/phpunit`）。
  - [ ] Fake が read/write の両SQL idを Query contract 通りに扱う。
  - [ ] `FakeSqlQueryTest.php` 相当が green。

### `app-resource-test`

**App ResourceのAPI contractをテストする**

- **ID:** `app-resource-test`
- **Aliases:** resource test, API test, HAL JSON test, test-hal-api-app, ResourceInterface, リソーステスト, APIテスト, 契約テスト
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** App Resourceのstatus code、body shape、write flowを固定したい。
- **着手前チェック（Before）:**
  - [ ] client視点でResourceを呼び、status / body / schema / hypermedia の期待を pin すると決めたか。
  - [ ] private method単位ではなく Resource の contract をテスト対象にすると理解したか。
  - [ ] テスト個別のbinding差し替えは `Injector::getOverrideInstance($context, $module)` で行うと理解したか。
- **Source:**
  - `tests/AbstractAppTestCase.php`
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/App/ArticlesTest.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Smoke/ResourceSmokeTest.php`
- **Key points:** client視点でResourceを呼び、status/body/schema/hypermediaの期待を pin する。例外経路もcontract — 必須field欠落（`ParameterException`）とschema違反（`ValidationException`）をメッセージまでassertする。`ResourceSmokeTest` は全GET resourceをfake引数で叩き、宣言schemaに対してrepresentationを検証する網羅smoke。
- **Do not:** 実装内部のprivate method単位を主テストにしない。
- **マスター確認（After）:**
  - [ ] テストが `ResourceInterface` 経由でResourceを呼んでいる（内部privateを直接叩いていない）。
  - [ ] status code と body shape を pin し green。
  - [ ] write後の副作用を再GETで確認している（PUT後のbody変化、DELETE後の404）。

### `page-resource-test`

**Page ResourceのHTML contractをテストする**

- **ID:** `page-resource-test`
- **Aliases:** page test, Qiq test, HTML resource test, html-test-hal-api-app, HTMLテスト, ページテスト, XSS回帰
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** Page Resourceとtemplateが期待するHTMLやstatusを固定したい。
- **着手前チェック（Before）:**
  - [ ] HTML context（`HtmlModule` + Fake）でDBなしに描画を検証すると決めたか。
  - [ ] 既定は `Visitor`、admin testでは fake admin session を明示注入すると理解したか。
- **Source:**
  - `tests/AbstractPageTestCase.php`
  - `tests/AbstractAdminPageTestCase.php`
  - `tests/Fake/FakeUserModule.php`
  - `tests/Resource/Page/ArticleTest.php`
  - `tests/Resource/Page/ArticleListTest.php`
- **Tests:**
  - `tests/Resource/Page/ArticleTest.php`
  - `tests/Resource/Page/IndexTest.php`
- **Key points:** HTML contextは `HtmlModule` とFakeを合成してDBなしで描画を検証する。描画は `$ro->toString()` で行い `$ro->view` と同一であることを pin。escapingはfake dataの `xss-regression` fixture（id=51）で回帰テストする。404もcontract — statusだけでなくError templateの描画内容まで pin する。
- **Do not:** Page testのためだけに実DBを必須にしない。
- **マスター確認（After）:**
  - [ ] Page test がDBなしで green。
  - [ ] HTML出力の要素・status を pin し `Resource/Page/ArticleTest.php` 相当が green。

### `hypermedia-workflow-test`

**Link/Embedを辿るworkflowをテストする**

- **ID:** `hypermedia-workflow-test`
- **Aliases:** hypermedia test, HAL workflow, follow links, `_links`, `_embedded`, rel naming, `#[Depends]`, href, ワークフローテスト, 遷移テスト, ユーザーストーリー
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** API clientがHAL linkやembedを使って遷移できることを固定したい。
- **着手前チェック（Before）:**
  - [ ] Resource単体のbody assertだけでなく、rel を辿る遷移可能性をテストすると決めたか。
  - [ ] rel名の層分離（link=Choreography / embed=Taxonomy）を検証対象に含めると理解したか。
- **Source:**
  - `tests/Hypermedia/AbstractWorkflowTestCase.php`
  - `tests/Hypermedia/ReaderBrowsesByCategoryTest.php`
  - `tests/Hypermedia/ReaderBrowsesByTagTest.php`
  - `tests/Hypermedia/EditorManagesArticleTest.php`
  - `docs/conventions.md`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** 1クラス = 1ユーザーストーリー。各stepは `#[Depends]` で前stepの ResourceObject を受け取り、`follow()`（内部は `$resource->href($rel, $vars, $ro)`）で `_links` の rel を解決して遷移する — URIハードコード遷移は禁止。`_embedded` は遷移対象ではなくenvelope contract（`HalEnvelopeContractTest` が層分離をassert）。POST起点のstoryは `Location` headerからidを取り出して次stepへ渡す。
- **Do not:** Resource単体のbody assertだけでhypermedia contractを済ませない。
- **マスター確認（After）:**
  - [ ] テストが `_links` の rel を `href()` で解決して次Resourceへ遷移している。
  - [ ] reader/editor の代表workflowが `HalEnvelopeContractTest.php` 相当で green。

### `mysql-integration-test`

**実DB経路を必要時だけ検証する**

- **ID:** `mysql-integration-test`
- **Aliases:** MySQL integration, real DB test, migrations, seed, skip when unavailable, 統合テスト, 実DBテスト, マイグレーション, シード
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** SQL、migration、real backendの代表経路を確認したい。
- **着手前チェック（Before）:**
  - [ ] default test suite は hermetic に保ち、MySQL integration は接続不可なら skip すると決めたか。
  - [ ] 全開発者にMySQL起動を必須にしないと理解したか。
  - [ ] setUpで書き換えたDB接続envはtearDownで復元し、後続の非MySQL suiteに漏らさない構造を保つと理解したか。
- **Source:**
  - `tests/Integration/AbstractMySQLTestCase.php`
  - `tests/Integration/ArticleMySQLTest.php`
  - `migrations.php`
  - `bin/seed.php`
- **Tests:**
  - `tests/Integration/ArticleMySQLTest.php`
  - `tests/Integration/AuthorMySQLTest.php`
  - `tests/Integration/CategoryMySQLTest.php`
  - `tests/Integration/TagMySQLTest.php`
  - `tests/Integration/MediaMySQLTest.php`
- **Key points:** default test suiteはhermetic。MySQL integrationは接続不可ならskipする。接続先は `MYSQL_TEST_DSN` / `MYSQL_TEST_USER` / `MYSQL_TEST_PASSWORD` で上書き可能。setUp毎に全テーブルdrop → migrate → seedで状態をリセットし、contextは `hal-api-app`（Fakeでなく実SqlQuery経路）を使う。
- **Do not:** 全開発者にMySQL起動を必須にしない。
- **マスター確認（After）:**
  - [ ] MySQL不在時にintegration testが fail ではなく skip する。
  - [ ] MySQL起動時に migration+seed 経由で代表CRUDが green。

## Semantic / generated artifacts

### `alps-profile-ssot`

**ALPS profileを意味のSSOTにする**

- **ID:** `alps-profile-ssot`
- **Aliases:** ALPS, semantic profile, ontology, taxonomy, choreography, SSOT, profile.json, app-state-diagram, ALPSプロファイル, 語彙, 意味論
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/apidoc.html
- **Use when:** Resource名、rel名、入力語彙を意味モデルから揃えたい。
- **着手前チェック（Before）:**
  - [ ] 語彙の出所を `var/alps/profile.json`（SSOT）に一本化すると決めたか。
  - [ ] Ontology（語彙）/ Taxonomy（名詞）/ Choreography（遷移名）の3層を区別したか。
- **Source:**
  - `var/alps/profile.json`
  - `apidoc.xml`
  - `docs/alps.md`
  - `docs/architecture.md`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** Ontologyは `title`（def: schema.org/headline）などのfield語彙、Taxonomyは `Article` などの名詞、Choreographyは遷移名 — safe遷移は `go*`（GET）、unsafe遷移は `do*`（POST/PUT/DELETE、冪等writeはidempotent属性）。ALPS遷移名はHAL `_links` rel / Resource URIと1:1に対応する（`goArticle` → `app://self/article`）。`apidoc.xml` の `<alps>` 指定でApiDoc / openapi.json / llms.txt生成が同じprofileを共有する — profileがSSOTである根拠。
- **Do not:** HAL link relとembed relに同じ命名層を使わない。
- **マスター確認（After）:**
  - [ ] Resource名・rel名・入力語彙が profile.json の定義と一致。
  - [ ] link rel=Choreography / embed rel=Taxonomy の層分離が守られている。

### `semantic-fake-data`

**semantic-exで決定的fake dataを作る**

- **ID:** `semantic-fake-data`
- **Aliases:** fake data, semantic-ex, deterministic data, observations, seed source, フェイクデータ, 決定的データ, シードデータ, 参照整合性
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** DBなしテストとreal DB seedの両方で使う代表データを生成したい。
- **着手前チェック（Before）:**
  - [ ] fake data を決定的（`mt_srand(42)`）かつ参照整合性ありで生成すると決めたか。
  - [ ] 同じfakeを no-DB テストと real seed の共通入力にすると理解したか。
- **Source:**
  - `bin/semantic-ex/gen-fake.php`
  - `bin/semantic-ex/gen-schemas.php`
  - `var/fake/article.json`
  - `var/fake/author.json`
  - `var/fake/observations.md`
  - `bin/seed.php`
- **Tests:**
  - `tests/Smoke/FakeSqlQueryTest.php`
  - `tests/Smoke/SqlSmokeTest.php`
- **Key points:** fake dataは決定的で、参照整合性を持ち、Fakeとreal seedの共通入力になる。`observations.md` は `gen-fake.php` ではなく `gen-schemas.php`（Phase 2）が生成し `composer schema` で更新される。id=51 `xss-regression` はPage escaping検証用のsupplemental fixtureで、schema導出時には除外される。
- **Do not:** testごとに意味の違うfixtureを散らさない。
- **マスター確認（After）:**
  - [ ] `composer fake` を2回実行しても出力 `var/fake/*.json` が同一（決定的）。
  - [ ] 同じfakeで no-DB テストと seed が成立する。

### `json-schema-generated`

**fake observationからJSON Schemaを生成する**

- **ID:** `json-schema-generated`
- **Aliases:** generated schema, JSON Schema, semantic-ex constraints, response schema, validation schema, スキーマ生成, 制約導出, 観測
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** 実例データから観察した制約をschemaとして固定したい。
- **着手前チェック（Before）:**
  - [ ] 制約を「前もって決める」のではなく fake observation から導出すると理解したか。
  - [ ] 生成対象はentity/list **response schema**（`var/json_schema/` の9件）であり、`var/json_validate/*.json`（input validation schema）と `write_response.json` 等は手書き維持と理解したか。
- **Source:**
  - `bin/semantic-ex/gen-schemas.php`
  - `var/json_schema/article.json`
  - `var/json_schema/articleList.json`
  - `var/json_validate/article_create.json`
  - `var/json_validate/article_update.json`
- **Tests:**
  - `tests/Smoke/ResourceSmokeTest.php`
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`
- **Key points:** 生成response schemaとinput validation schema（手書き）をResourceの `#[JsonSchema]` に接続する。導出規則: `maxLength` は観測最大長×1.5を50/100/200/500/1000…のnice境界へ切り上げ、`minLength` は観測最小長。`ResourceSmokeTest` が全GET resourceを宣言schemaに対して検証する。
- **Do not:** Resource bodyを変えたのにschema更新を忘れない。
- **マスター確認（After）:**
  - [ ] `composer schema` で生成対象のresponse schemaが再生成され、Resourceの `#[JsonSchema]` 参照と一致。
  - [ ] body変更時にschema更新を伴い `ArticleTest.php` 相当が green。

### `apidoc-llms-generated`

**API docsとllms.txtを生成する**

- **ID:** `apidoc-llms-generated`
- **Aliases:** ApiDoc, OpenAPI, llms.txt, docs generation, `composer doc`, API documentation, Tool Use, AI instrument, APIドキュメント, ドキュメント自動生成
- **Status:** `support`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/apidoc.html
- **Use when:** Resource、schema、ALPSから人間向け・AI向けのAPI資料を生成したい。
- **着手前チェック（Before）:**
  - [ ] ドキュメントを手書きで固定せず、Resource/schema/ALPS から生成すると決めたか。
  - [ ] 出力の役割分担を理解したか — 人間向けは `docs/index.html`（ApiDoc HTML）、ツールチェーン統合用は `docs/openapi.json`（OpenAPI 3.1）、AI向け入口は `docs/llms.txt`。
- **Source:**
  - `apidoc.xml`
  - `docs/openapi.json`
  - `docs/llms.txt`
  - `docs/index.html`
  - `docs/audit.md`
  - `composer.json`
- **Tests:**
  - なし（生成物を検証する自動テストは無い。`composer doc` 後の `git diff docs/` を目視確認）
- **Key points:** `composer doc` がApiDocとALPS HTMLを生成する。生成フォーマットは `apidoc.xml` の `<format>`（本リポジトリは html / openapi / llms / audit / terms）が決める。llms.txtにはAPI endpoint / ResourceObject / Query・Command interface / SQL / entity定義が含まれ、URI・型・schemaがそのままAIのtool定義（Tool Use / MCP instrument）に使える。
- **Do not:** 手書きドキュメントだけを正とし、Resourceやschemaとの同期を失わない。
- **マスター確認（After）:**
  - [ ] `composer doc` でAPI資料が再生成され、Resource/schemaと同期する。
  - [ ] 生成物（openapi.json / llms.txt）が現在のルートとResponseを反映している。

## Manual-only（型のみ記述 — 公式マニュアル準拠）

このセクションのKataは、BEAR.Sundayに機能が存在するがこのリポジトリに正規実装・テストがまだ無いものです。`Manual:` の公式マニュアル章を一次資料として読み、**近いKata** の実装済みの型（命名・分離・テスト形）を流用して移植します。マスター確認は自プロジェクトに書いたテストのgreenが最終確証です。

### `api-patch-partial-update`

**PATCHで差分更新を受ける**

- **ID:** `api-patch-partial-update`
- **Aliases:** PATCH, onPatch, partial update, delta update, 差分更新, 部分更新
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** リソース全体の置換（PUT）ではなく、差分のみを適用する更新を公開したい。
- **近いKata:** `api-put-tristate-input`（省略=維持のtri-state入力の型はそのまま流用できる）
- **着手前チェック（Before）:**
  - [ ] PUT（全置換・冪等）とPATCH（差分適用・非冪等）の意味論の違いを理解したか。
  - [ ] `onPatch` はBEAR.Sundayがネイティブ対応（`onGet`/`onPost`/`onPut`/`onPatch`/`onDelete` の統一インターフェース）と理解したか。
  - [ ] 「省略されたフィールドは維持」の入力設計に `api-put-tristate-input` のtri-state型を流用すると決めたか。
- **Key points:** method安全性表でPATCHは Safe=No / Idempotent=No。PUT/PATCH/DELETEのbodyは `content-type`（`application/json` / `x-www-form-urlencoded`）に応じて引数に渡る。
- **Do not:** PATCHにPUTの全置換セマンティクスを持ち込まない。差分適用の意味（維持/削除/置換）を暗黙にしない。
- **マスター確認（After）:**
  - [ ] 省略フィールドが維持され、指定フィールドのみ更新されることを自プロジェクトのtestでpinしてgreen。

### `api-options-method`

**OPTIONSでメソッドとパラメータ仕様を返す**

- **ID:** `api-options-method`
- **Aliases:** OPTIONS, OptionsMethodModule, Allow header, method discovery, API discovery, メソッド発見
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/tutorial.html（OPTIONS応答例）, https://bearsunday.github.io/manuals/1.0/en/production.html（本番でのenable）
- **Use when:** クライアントが利用可能なHTTPメソッドと必要パラメータを実行時に発見できるようにしたい。
- **近いKata:** `json-schema-validation`（パラメータ仕様の宣言はsignatureとschemaが担う）
- **着手前チェック（Before）:**
  - [ ] OPTIONS応答（`Allow` header + method別のparameters/required JSON）はResourceのsignatureから自動生成され、自分で `onOptions` を書かないと理解したか。
  - [ ] devコンテキストでは有効、productionでは `$this->override(new OptionsMethodModule)` で明示的にenableすると理解したか（backing classは `vendor/bear/resource/src/Module/OptionsMethodModule.php`）。
- **Key points:** `curl -i -X OPTIONS <uri>` が `Allow` headerとmethod別のparameters/required（型付き）をJSONで返す（RFC7231 §4.3.7）。OPTIONSはGET同様にsafe/idempotent。
- **Do not:** OPTIONS応答を静的ファイルで手書きしない（Resource signatureがSSOT）。
- **マスター確認（After）:**
  - [ ] `curl -i -X OPTIONS` で `Allow` headerとparameters JSONが返り、Resource signatureの変更に追従する。

### `content-negotiation`

**AcceptヘッダでコンテキストをスイッチしJSON/HTML/CSV等を出し分ける**

- **ID:** `content-negotiation`
- **Aliases:** content negotiation, BEAR.Accept, #[Produces], Accept-Language, Vary, media type, コンテントネゴシエーション, 表現切替
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/content-negotiation.html
- **Use when:** 同一URIで `Accept` / `Accept-Language` に応じた表現（media type・言語）を返したい。
- **近いKata:** このリポジトリはcontextの静的分離（`hal-api-app`=HAL JSON / `html-hal-app`=Qiq HTML）で複数表現を提供している。実行時ネゴシエーションが必要な時のみこの型を使う。
- **着手前チェック（Before）:**
  - [ ] `composer require bear/accept` が必要（このリポジトリには未インストール）と確認したか。
  - [ ] `Accept*` header → context のマップを `var/locale/available.php` に置くと理解したか。
  - [ ] アプリ全体（`public/index.php` で `Accept` クラスを使いcontextを決定）かresource単位（`AcceptModule` + `#[Produces]`）かを選んだか。
- **Key points:** `#[Produces(['application/hal+json', 'text/csv'])]` は左から優先度順。resource単位では `Vary` headerが自動付与される（アプリ全体方式では手動）。representationの生成はcontextual rendererが担う。
- **Do not:** Resource内で `$_SERVER['HTTP_ACCEPT']` を手でparseしない。`Vary` 無しでnegotiated responseをキャッシュさせない。
- **マスター確認（After）:**
  - [ ] `Accept` header別に表現が切り替わり、`Vary` が付くことを自プロジェクトのtestでpinしてgreen。

### `form-validation-webform`

**Ray.WebFormModuleでAOPフォームバリデーション**

- **ID:** `form-validation-webform`
- **Aliases:** WebFormModule, #[FormValidation], #[InputValidation], Aura.Input, form class, vnd.error, フォームバリデーション
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/form.html
- **Use when:** フィールド定義・検証ルール・描画helperを1つのform classに集約し、AOPで検証を差し込みたい。
- **近いKata:** `admin-prg-form`（このリポジトリの正規形: JSON Schema + Input DTO + PRG + CSRF）。WebFormはそれに代わる別方式であり、同じ境界で併用しない。
- **着手前チェック（Before）:**
  - [ ] このリポジトリの正規形（`#[JsonSchema(params:)]` + `#[Input]` DTO + PRG）で足りないか先に確認したか。
  - [ ] `composer require ray/web-form-module` が必要（未インストール）と確認したか。
  - [ ] form classは `AbstractForm` を継承し `init()` でfield/ruleを定義、検証は `#[FormValidation(form:, onFailure:)]` で差し込むと理解したか。
- **Key points:** 検証失敗時は `onFailure` メソッドへ分岐。API用途は `#[InputValidation]` → `ValidationException`（`$e->error` が vnd.error+json）。CSRFはopt-in — `SetAntiCsrfTrait`（form単位）または `#[CsrfProtection]`（action単位）で、いずれも無ければCSRF検証は行われない。
- **Do not:** WebFormとJSON Schema validationで同じ境界を二重検証しない。CSRF未設定のままwrite formを公開しない。
- **マスター確認（After）:**
  - [ ] 検証成功/失敗の両経路（onFailure分岐またはValidationException）を自プロジェクトのtestでpinしてgreen。

### `web-context-param-binding`

**Webコンテキスト値と他Resource値をmethod引数に束縛する**

- **ID:** `web-context-param-binding`
- **Aliases:** #[CookieParam], #[QueryParam], #[FormParam], #[ServerParam], #[EnvParam], #[ResourceParam], web context binding, superglobal binding, クッキー束縛
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** `$_COOKIE` / `$_SERVER` / `$_ENV` の値や他Resourceの結果を、method内で取得せず引数として宣言的に受けたい。
- **近いKata:** `api-post-input-dto`（引数境界の宣言的設計という同じ型）
- **着手前チェック（Before）:**
  - [ ] superglobal直読みをやめ、`#[CookieParam('id')] string $tokenId = "0000"` のように引数束縛（default値でunset時の挙動を明示）にすると決めたか。
  - [ ] client指定値が優先される（テストで上書き可能）と理解したか。
  - [ ] 他Resourceの値は `#[ResourceParam('app://self//login#nickname')]`（URI fragment = body key）で受けると理解したか。
- **Key points:** `Ray\WebContextParam\Annotation\{QueryParam, CookieParam, EnvParam, FormParam, ServerParam}` で `$_GET`/`$_COOKIE`/`$_ENV`/`$_POST`/`$_SERVER` を束縛。`#[ResourceParam]` はmethod呼び出し時に対象resourceへGET requestを発行する。enum型引数は値を制限し、範囲外は `ParameterInvalidEnumException`。
- **Do not:** Resource内で `$_COOKIE` 等を直読みしない。`#[ResourceParam]` で重いresourceを無自覚に毎回呼ばない。
- **マスター確認（After）:**
  - [ ] method内にsuperglobal参照が無く（grepで空）、引数束縛のみで値が渡ることを自プロジェクトのtestでpinしてgreen。

### `db-transactional`

**`#[Transactional]`で複数書き込みを原子化する**

- **ID:** `db-transactional`
- **Aliases:** Transactional, transaction, rollback, TransactionalModule, トランザクション, 原子性
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.aura.html
- **Use when:** 複数のwrite（例: entity本体 + link table）を1トランザクションで原子化したい。
- **近いKata:** `db-link-table-sync`（clear→linkループはトランザクション化の典型候補）
- **着手前チェック（Before）:**
  - [ ] トランザクション境界をResource method（use case境界）に置き、Query/Command interfaceには持ち込まないと決めたか。
  - [ ] `Ray\AuraSqlModule\Annotation\Transactional` をmethodに付けるとAOPでbegin/commit/rollbackされる（backing classは `vendor/ray/aura-sql-module/src/TransactionalInterceptor.php`、このリポジトリにインストール済み）と理解したか。
  - [ ] 複数接続DBは `#[Transactional(["pdo", "userDb"])]` のようにproperty指定すると理解したか。
- **Key points:** 例外throwでrollback。AuraSqlModule系のbindingにinterceptorが含まれる。
- **Do not:** トランザクション内で外部API呼び出しなどrollback不能な副作用を起こさない。
- **マスター確認（After）:**
  - [ ] 途中失敗時に先行writeがrollbackされることを自プロジェクトのtestでpinしてgreen。

### `aop-validation-valid`

**`#[Valid]`/`#[OnValidate]`/`#[OnFailure]`でAOPバリデーション**

- **ID:** `aop-validation-valid`
- **Aliases:** #[Valid], #[OnValidate], #[OnFailure], Ray.ValidateModule, AOP validation, validation separation, 検証分離
- **Status:** `manual-only`
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** 検証ロジックをmethod本体からAOPで分離したい（Aura.Filter / Respect\Validation等と組み合わせ可能）。
- **近いKata:** `json-schema-validation`（このリポジトリの正規形。宣言的スキーマで表現できる形状検証はそちら）
- **着手前チェック（Before）:**
  - [ ] `composer require ray/validate-module` が必要（未インストール）と確認したか。
  - [ ] `#[OnValidate]` methodは元methodと同じ引数を取り `Validation` を返す、失敗時は `#[OnFailure]` へ分岐（無ければ `InvalidArgumentException`）と理解したか。
- **Key points:** `#[Valid('foo')]` の名前付きで1クラス複数バリデーションを共存できる。`#[OnFailure]` では `$failure->getMessages()` / `$failure->getInvocation()` で失敗詳細と元呼び出しへアクセスできる。
- **Do not:** JSON Schemaで表現できる形状検証をAOP validationに重複させない。
- **マスター確認（After）:**
  - [ ] 検証分離後もmethod本体に検証分岐が残っていないことを確認し、成功/失敗経路を自プロジェクトのtestでpinしてgreen。
