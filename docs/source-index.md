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
- サンプルの位置付けは次の4種類です。

| Status | 意味 |
|---|---|
| `canonical` | 通常の実装で最初に真似する正規形 |
| `showcase` | 特定機能を切り出して見せる実例 |
| `comparison-only` | 比較理解用。デフォルト実装としてコピーしない |
| `support` | テスト、Fake、生成物など正規形を支える周辺実装 |

## 使い方

1. `Aliases` にある語で検索します。例: `streaming`, `DbQuery`, `PRG`, `FakeSqlQuery`。
2. `Status` で、そのコードをコピーしてよい正規形か、比較用かを確認します。
3. **着手前チェック** で、書き始める前に守るべき型と前提を確認します。
4. `Source` を読みます。
5. `Tests` を読み、期待される振る舞いを確認します。
6. 実装後に **マスター確認** のチェックリストを自分のコードに対して走らせ、全項目が満たされたらそのKataをマスターしたと判断します。マスター確認は「`Tests` に挙げたテストを自分の実装へ写経して green になること」を最終確証とします。

## 索引（一覧）

| Kata | Status | 何をするか |
|---|---|---|
| [`db-read-one-entity`](#db-read-one-entity) | canonical | DBから主キーで1件のEntityを読む |
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
| [`admin-auth-boundary`](#admin-auth-boundary) | showcase | AdminGuardによるauthor-scoped認可境界 |
| [`import-app`](#import-app) | showcase | ImportAppModuleで他アプリのResourceを呼ぶ |
| [`event-extraction`](#event-extraction) | showcase | Semantic Logger観察ログからEventを抽出する |
| [`event-filter-replay`](#event-filter-replay) | showcase | Eventsをフィルタしてreplayする |
| [`event-store-persistence`](#event-store-persistence) | support | EventStoreInterfaceでEventを永続化する |
| [`resource-observation-bridge`](#resource-observation-bridge) | showcase | BEAR.Resource実行から観察ログを生成する |
| [`defer-resource-request`](#defer-resource-request) | showcase | `#[Defer]` + `#[Link]`で応答後にfollow-upを実行する |
| [`defer-conditional`](#defer-conditional) | showcase | `DeferInterface::add()`で条件付きdeferを手動制御する |

## Data access / BDR

### `db-read-one-entity`

**DBから主キーで1件のEntityを読む**

- **ID:** `db-read-one-entity`
- **Aliases:** read one row, fetch entity, primary key lookup, item query, `#[DbQuery]`, BDR read, article detail
- **Status:** `canonical`
- **Use when:** 主キーで1件取得し、型付きEntityとしてResourceで使いたい。
- **着手前チェック（Before）:**
  - [ ] read用の `<Entity>QueryInterface` を、write用Commandと分けて用意したか。
  - [ ] 1件取得methodを `item(int $id): <Entity>|null` のシグネチャにするか決めたか。
  - [ ] SQLは `<entity>_item.sql` という命名でファイルに置くと決めたか。
  - [ ] `SELECT` のカラム順を Entity constructor の引数順に合わせる前提を理解したか（`FetchNewInstance` は `PDO::FETCH_FUNC`）。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Query/ArticleQueryInterface.php::item()`
  - `src/Entity/Article.php`
  - `var/db/sql/article_item.sql`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** `item(int $id): Article|null`、SQLファイルは `article_item.sql`、ResourceはSQLを直接持たない。
- **Do not:** Resource内にSQLを書く。templateからDBを読む。
- **マスター確認（After）:**
  - [ ] Resource class に SQL 文字列が無い（`grep -i select src/Resource/App/<Name>.php` が空）。
  - [ ] Query method が `item(int $id): <Entity>|null` 型を返す。
  - [ ] `var/db/sql/<entity>_item.sql` が存在し、`SELECT` カラム順が Entity constructor 引数順と一致。
  - [ ] `ArticleTest.php` 相当を写経し、存在IDで200・型付きbody、未存在IDで404を pin して green。

### `db-read-by-natural-key`

**natural keyで1件読む**

- **ID:** `db-read-by-natural-key`
- **Aliases:** natural key lookup, bySlug, byEmail, byFilename, after insert lookup, unique key read
- **Status:** `canonical`
- **Use when:** clientが指定した一意な値で再取得したい。特にINSERT後に新規IDを回収したい。
- **着手前チェック（Before）:**
  - [ ] 対象Entityに slug / email / filename のような自然キー（一意制約）があるか確認したか。
  - [ ] 取得methodを `by<NaturalKey>()`（`bySlug`/`byEmail`/`byFilename`）と命名すると決めたか。
  - [ ] INSERT後の新規ID回収を `lastInsertId()` ではなく自然キー再SELECTで行う方針を理解したか。
- **Source:**
  - `src/Query/ArticleQueryInterface.php::bySlug()`
  - `src/Resource/App/Article.php::onPost()`
  - `var/db/sql/article_by_slug.sql`
  - `src/Query/AuthorQueryInterface.php::byEmail()`
  - `src/Query/MediaQueryInterface.php::byFilename()`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Smoke/FakeSqlQueryTest.php`
- **Key points:** INSERT後は `lastInsertId()` ではなく、`bySlug()` などのnatural keyで再SELECTする。
- **Do not:** driver依存のID状態をResourceの標準経路に持ち込む。
- **マスター確認（After）:**
  - [ ] write path に `lastInsertId` が登場しない（`grep -ri lastinsertid src/` が空）。
  - [ ] `by<Key>()` method と `<entity>_by_<key>.sql` が対応して存在。
  - [ ] POST後に自然キーで再取得し新規IDを body へ返すフローを `ArticleTest.php` 相当で green。

### `db-read-list-pager`

**DBから一覧をページングして読む**

- **ID:** `db-read-list-pager`
- **Aliases:** list query, collection resource, pager, `PagesInterface`, `#[Pager]`, article list, filtering
- **Status:** `canonical`
- **Use when:** collection resourceで一覧、絞り込み、ページングを扱いたい。
- **着手前チェック（Before）:**
  - [ ] item resource（1件）と collection resource（一覧）を別Resourceに分けると決めたか。
  - [ ] 一覧methodを `list(...)` と命名し、SQLを `<entity>_list.sql` に置くと決めたか。
  - [ ] ページングを `#[Pager(perPage: 'perPage')]` と `PagesInterface` で扱う前提を理解したか。
- **Source:**
  - `src/Resource/App/Articles.php::onGet()`
  - `src/Query/ArticleQueryInterface.php::list()`
  - `var/db/sql/article_list.sql`
  - `src/Resource/Page/ArticleList.php::onGet()`
- **Tests:**
  - `tests/Resource/App/ArticlesTest.php`
  - `tests/Resource/Page/ArticleListTest.php`
- **Key points:** collectionは `list()`、SQLは `article_list.sql`、`perPage` は `#[Pager(perPage: 'perPage')]` と対応する。
- **Do not:** item resourceに一覧責務を混ぜる。template側でページング計算を始める。
- **マスター確認（After）:**
  - [ ] 一覧Resourceが item Resourceと別クラスになっている。
  - [ ] `list()` method の戻り値が `PagesInterface`、`#[Pager]` の `perPage` 名が parameter 名と一致。
  - [ ] filter（categoryId/tagId/status 等）省略時と指定時の件数差を `ArticlesTest.php` 相当で green。

### `db-command-write`

**DB書き込みをCommand Interfaceに分ける**

- **ID:** `db-command-write`
- **Aliases:** write command, command interface, POST, PUT, DELETE, add update delete, CQRS split
- **Status:** `canonical`
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
  - `var/db/sql/article_add.sql`
  - `var/db/sql/article_update.sql`
  - `var/db/sql/article_delete.sql`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Integration/ArticleMySQLTest.php`
- **Key points:** readは `<Entity>QueryInterface`、writeは `<Entity>CommandInterface` に分ける。write methodは `add`, `update`, `delete` の命令形。
- **Do not:** read/write methodを同じinterfaceに混ぜる。
- **マスター確認（After）:**
  - [ ] `<Entity>QueryInterface` に write method が、`<Entity>CommandInterface` に read method が混ざっていない。
  - [ ] write method 名が命令形（add/update/delete）で SQL ファイル名と対応。
  - [ ] POST/PUT/DELETE の各経路を `ArticleTest.php` 相当で green。

### `db-link-table-sync`

**link tableをclear/linkで同期する**

- **ID:** `db-link-table-sync`
- **Aliases:** many-to-many, tagIds, link table, clear links, replace relation, article tags
- **Status:** `canonical`
- **Use when:** 記事とタグのような関連テーブルを、入力されたIDリストに置き換えたい。
- **着手前チェック（Before）:**
  - [ ] 多対多の更新を「全削除→再リンク」の置換戦略で行うと決めたか。
  - [ ] link用Commandに `clear($parentId)` と `link($parentId, $childId)` を用意すると決めたか。
  - [ ] link tableのSQLをResourceに書かず `<rel>_clear.sql` / `<rel>_link.sql` に置くと決めたか。
- **Source:**
  - `src/Resource/App/Article.php::syncTags()`
  - `src/Query/ArticleTagCommandInterface.php`
  - `var/db/sql/article_tag_clear.sql`
  - `var/db/sql/article_tag_link.sql`
  - `tests/Fake/FakeSqlQuery.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Smoke/FakePostQueryRows.php`
- **Key points:** relation更新は `clear($articleId)` 後に `link($articleId, $tagId)` を繰り返す。
- **Do not:** Resource内でlink tableのSQLを直接組み立てる。
- **マスター確認（After）:**
  - [ ] Resource に link table の `INSERT`/`DELETE` 文字列が無い。
  - [ ] 更新フローが `clear()` → `link()` ループになっている。
  - [ ] tagIds を別リストに変更した時に関連が置換されることを `ArticleTest.php` 相当で green。

### `db-result-projection`

**Query結果を専用Result objectにする**

- **ID:** `db-result-projection`
- **Aliases:** query projection, read model, SELECT result, `PostQueryInterface`, generator traversal, feed item
- **Status:** `showcase`
- **Use when:** canonical Entityそのものではなく、表示関心ごとのread-side projectionを作りたい。
- **着手前チェック（Before）:**
  - [ ] 表示用の加工（絞り込み、整形）を汎用Entityやtemplateに入れたくない理由を明確にしたか。
  - [ ] Result objectを `PostQueryInterface` 実装にし、named method（`published()` 等）で意図を表すと決めたか。
- **Source:**
  - `src/Query/ArticleSelectionQueryInterface.php`
  - `src/Result/ArticleSelection.php`
  - `var/db/sql/article_selection_list.sql`
- **Tests:**
  - `tests/Smoke/MediaQuerySamplesTest.php`
- **Key points:** `ArticleSelection::published()` のようなnamed methodでtemplate側の条件分岐を減らす。Result objectは `IteratorAggregate` / `Countable` を実装し、`fromContext()` で構築する。
- **Do not:** presentation専用の加工を汎用Entityやtemplateに押し込む。
- **マスター確認（After）:**
  - [ ] Result class が `PostQueryInterface` を実装し、表示意図を表す named method を持つ。
  - [ ] template / Resource 側に同じ絞り込みロジックが重複していない。
  - [ ] `MediaQuerySamplesTest.php` 相当（`titles()` / `published()->count()` / `first()`）を写経して green。

### `db-array-row-comparison`

**Entityではなくarrayで読む比較例を見る**

- **ID:** `db-array-row-comparison`
- **Aliases:** array row, no entity, row array, `type: row`, migration comparison
- **Status:** `comparison-only`
- **Use when:** Entityを使わない実装と正規形の責務差を理解したい。
- **着手前チェック（Before）:**
  - [ ] これは正規形ではなく**比較学習用**であると理解したか（デフォルト採用しない）。
  - [ ] 何を比較したいか（型変換・日付正規化・row shape防衛がどこに寄るか）を意識したか。
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
- **Aliases:** `SqlQueryInterface`, multi query, previous next article, reading time, programmatic query
- **Status:** `comparison-only`
- **Use when:** 1つの `#[DbQuery]` methodに収まらない複数SQLの調停を理解したい。
- **着手前チェック（Before）:**
  - [ ] これは比較学習用で、単純な1件取得には使わないと理解したか。
  - [ ] 標準形は Query Interface + `#[DbQuery]`、これは複数SQL調停が必要な時のみと区別できたか。
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
- **Aliases:** raw PDO, `ExtendedPdoInterface`, inline SQL, framework comparison, MediaQuery responsibility
- **Status:** `comparison-only`
- **Use when:** Ray.MediaQueryが外部化している責務を低レベル比較で理解したい。
- **着手前チェック（Before）:**
  - [ ] これは比較学習用で、正規のApp Resourceにinline SQLを戻さないと理解したか。
  - [ ] Ray.MediaQueryが肩代わりしている責務（SQL外部化・bind・fetch・型変換）を意識したか。
- **Source:**
  - `src/Resource/App/Variations/ArticleRawPdo.php`
  - `tests/Fake/FakeExtendedPdoProvider.php`
- **Tests:**
  - `tests/Resource/App/Variations/ArticleRawPdoTest.php`
- **Key points:** SQL、bind、fetch、型変換、日付正規化がResource近くに現れる。
- **Do not:** 正規のApp Resourceにinline SQLを戻さない。
- **マスター確認（After）:**
  - [ ] Raw PDO版でResourceに現れる5つの責務（SQL/bind/fetch/型変換/日付）を指摘できる。
  - [ ] それらがMediaQuery正規形ではどこへ移るか説明できる。

## Resource / API

### `api-get-hal-resource`

**GET ResourceをHAL+JSONで返す**

- **ID:** `api-get-hal-resource`
- **Aliases:** GET resource, HAL JSON, ResourceObject body, API item resource, `onGet`
- **Status:** `canonical`
- **Use when:** App Resourceで1件の状態をAPI表現として返したい。
- **着手前チェック（Before）:**
  - [ ] ResourceObject は状態を `$this->body` に置き、表現（JSON化）は renderer に任せると理解したか。
  - [ ] `#[Embed]` を使う場合は scalar を `$this->body += [...]`、使わない場合は `$this->body = [...]` と決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/App/Author.php::onGet()`
  - `docs/resources.md`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** ResourceObjectは状態を `$this->body` に置く。表現はrendererが作る。
- **Do not:** ResourceでJSON文字列を手作りしない。
- **マスター確認（After）:**
  - [ ] Resource に `json_encode` や手書きJSON文字列が無い。
  - [ ] body が連想配列（または `#[Embed]` slot 付き）で構成されている。
  - [ ] `ArticleTest.php` 相当で200 + body shape を pin して green。

### `api-post-input-dto`

**POST入力をInput DTOで受ける**

- **ID:** `api-post-input-dto`
- **Aliases:** POST resource, create resource, Input DTO, `#[Input]`, Ray.InputQuery, request DTO
- **Status:** `canonical`
- **Use when:** 入力項目が多い作成処理を、Resource methodの前でDTO化したい。
- **着手前チェック（Before）:**
  - [ ] 入力が「多数 / tri-state / まとまった名前付きshape」のどれかで、DTO化が妥当か判断したか（短いflat入力なら scalar parameter のままでよい）。
  - [ ] DTOを `src/Input/<Entity><Verb>Input.php` に置き、`#[Input]` で受けると決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onPost()`
  - `src/Input/ArticleCreateInput.php`
  - `var/json_validate/article_create.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/params/query_args.php`
- **Key points:** Resource method parameterに `#[Input] ArticleCreateInput $input` を置く。schema validationは `#[JsonSchema(params: ...)]`。
- **Do not:** 多数の関連する入力を無理にflat scalar parameterへ増やし続けない。
- **マスター確認（After）:**
  - [ ] method signature が `#[Input] <Entity>CreateInput $input` になっている。
  - [ ] DTOの境界と `*_create.json` validation schema が同じ項目集合を守る。
  - [ ] 正常作成と検証エラーの両方を `ArticleTest.php` 相当で green。

### `api-put-tristate-input`

**PUTでtri-state入力を扱う**

- **ID:** `api-put-tristate-input`
- **Aliases:** PUT resource, update resource, tri-state, nullable array, tagIds, leave clear replace
- **Status:** `canonical`
- **Use when:** 省略、空配列、非空配列で異なる意味を持つ更新入力を扱いたい。
- **着手前チェック（Before）:**
  - [ ] 「省略（維持）/ 空（全削除）/ 非空（置換）」の3状態を区別する必要があるか確認したか。
  - [ ] `null` と `[]` を同一視しないと決めたか。DTO内で `mixed` を `is_array` gate に通すと決めたか。
- **Source:**
  - `src/Resource/App/Article.php::onPut()`
  - `src/Input/ArticleUpdateInput.php`
  - `var/json_validate/article_update.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** `tagIds === null` は維持、`[]` は全削除、listは置換。DTO内で `mixed` を `is_array` gateに通す。
- **Do not:** `null` と `[]` を同じ意味に潰さない。
- **マスター確認（After）:**
  - [ ] DTO が `null` / `[]` / 非空list の3分岐を保持している。
  - [ ] 省略・空・非空それぞれの結果差を `ArticleTest.php` 相当で個別に green。

### `api-delete-no-content`

**DELETE成功を204で返す**

- **ID:** `api-delete-no-content`
- **Aliases:** DELETE resource, no content, 204, delete command, not found before delete
- **Status:** `canonical`
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
- **Key points:** 削除前に存在確認し、成功時は `Code::NO_CONTENT` と空body。
- **Do not:** 削除済みや未存在を成功扱いにしない。
- **マスター確認（After）:**
  - [ ] 成功時 `$this->code = Code::NO_CONTENT` かつ body が空。
  - [ ] 未存在IDの削除が404になることを `ArticleTest.php` 相当で green。

### `not-found-response`

**見つからないResourceを404にする**

- **ID:** `not-found-response`
- **Aliases:** 404, not found, missing entity, error body, not found branch
- **Status:** `canonical`
- **Use when:** Queryが `null` を返した時にResourceで404を返したい。
- **着手前チェック（Before）:**
  - [ ] not-found を例外ではなくResource側で404 bodyとして表現すると決めたか。
  - [ ] Page template は4xxでも呼ばれるため guard を置くと理解したか。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/Page/Article.php::onGet()`
  - `templates/Page/Article.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** not-found readは例外ではなくResourceで404 bodyを置く。Page templateは4xxでも呼ばれるためguardを置く。
- **Do not:** `src/` からgeneric runtime exceptionを投げてnot-found表現にしない。
- **マスター確認（After）:**
  - [ ] `src/` に not-found用の generic `throw new \RuntimeException` 等が無い（必要なら `src/Exception/` のドメイン例外）。
  - [ ] Page template に entity 不在時の guard がある。
  - [ ] 未存在IDで App=404 / Page=404描画 を両testで green。

### `json-schema-validation`

**Request/ResponseをJSON Schemaで検証する**

- **ID:** `json-schema-validation`
- **Aliases:** `#[JsonSchema]`, response schema, params schema, validation, json_validate, json_schema
- **Status:** `canonical`
- **Use when:** Resourceの入力と出力のshapeを宣言的に固定したい。
- **着手前チェック（Before）:**
  - [ ] response schema は `schema:`、request params schema は `params:` に分けると理解したか。
  - [ ] DTO境界とvalidation schemaを同じ項目集合で揃えると決めたか。
- **Source:**
  - `src/Resource/App/Article.php`
  - `var/json_schema/article.json`
  - `var/json_validate/article_create.json`
  - `var/json_validate/article_update.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/params/query_args.php`
- **Key points:** response schemaは `schema:`、request params schemaは `params:`。DTOとschemaは同じ境界を守る。
- **Do not:** Resource body shapeをテストやschemaなしで暗黙に変えない。
- **マスター確認（After）:**
  - [ ] method に `#[JsonSchema(schema: ..., params: ...)]` が付き、対応する `var/json_schema` / `var/json_validate` ファイルが存在。
  - [ ] body shape を変えた時は schema も更新され、`ArticleTest.php` 相当が green。

### `hal-link`

**HAL `_links` を `#[Link]` で宣言する**

- **ID:** `hal-link`
- **Aliases:** HAL link, `_links`, `#[Link]`, affordance, Choreography rel, URI template
- **Status:** `canonical`
- **Use when:** clientが次に遷移できるResourceをHAL linkとして表したい。
- **着手前チェック（Before）:**
  - [ ] link rel に ALPS Choreography 名（`goArticleList` 等の遷移名）を使うと決めたか。
  - [ ] link（遷移名）と embed（Taxonomy名詞）の命名層を混ぜないと理解したか。
- **Source:**
  - `src/Resource/App/Article.php::onGet()`
  - `src/Resource/App/Articles.php::onGet()`
  - `var/alps/profile.json`
- **Tests:**
  - `tests/Hypermedia/ReaderBrowsesByCategoryTest.php`
  - `tests/Hypermedia/ReaderBrowsesByTagTest.php`
- **Key points:** link relはALPS Choreography名。例: `goArticleList`, `goAuthor`, `goCategory`。
- **Do not:** embed用のtaxonomy名とlink用のchoreography名を混ぜない。
- **マスター確認（After）:**
  - [ ] `#[Link(rel: ...)]` の rel が ALPS profile の Choreography 名と一致。
  - [ ] response の `_links` を辿る workflow test（`ReaderBrowsesBy*Test.php` 相当）が green。

### `hal-embed`

**HAL `_embedded` を `#[Embed]` と `addQuery()` で作る**

- **ID:** `hal-embed`
- **Aliases:** HAL embed, `_embedded`, `#[Embed]`, `addQuery`, embedded resource, Taxonomy rel
- **Status:** `canonical`
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
- **Key points:** `#[Embed]` が先にRequest slotを作るため、scalar fieldsは `$this->body += [...]` で足す。
- **Do not:** embed relに `go*` 名を使わない。embed slotを `$this->body = [...]` で上書きしない。
- **マスター確認（After）:**
  - [ ] `#[Embed]` を持つResourceが scalar を `+=` で足し、embed slot を `=` で潰していない。
  - [ ] embed rel が `go*` でない（Taxonomy名詞）。
  - [ ] `_embedded` に子Resourceが現れることを `HalEnvelopeContractTest.php` 相当で green。



### `auth-oauth-flow`

**OAuth認証フローをResourceで示す**

- **ID:** `auth-oauth-flow`
- **Aliases:** OAuth, Auth0, Google login, AuthInterface, authorization URL, token exchange, session auth
- **Status:** `showcase`
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
  - `src/Auth/NativeAuthSession.php`
- **Tests:**
  - `tests/Resource/App/AuthTest.php`
  - `tests/Smoke/GoogleAuthProviderSmokeTest.php`
- **Key points:** `AuthInterface` でproviderを抽象化。GET→authorization URL、POST→token exchange。失敗は401でprovider内部を漏らさない。
- **Do not:** provider固有の例外や内部メッセージをresponse bodyに含めない。
- **マスター確認（After）:**
  - [ ] `AuthInterface` binding が test と prod で切り替わる（FakeAuthProvider vs GoogleAuthProvider）。
  - [ ] GET で authorizationUrl が返り、POST で authenticated user が返ることを `AuthTest.php` 相当で green。

### `file-upload-input`

**`#[InputFile]`でファイルアップロードを受ける**

- **ID:** `file-upload-input`
- **Aliases:** file upload, InputFile, FileUpload, media upload, MIME validation, binary upload
- **Status:** `canonical`
- **Use when:** HTTP multipartアップロードでファイルを受け取り、検証して保存したい。
- **着手前チェック（Before）:**
  - [ ] `#[InputFile]` で `FileUpload|ErrorFileUpload` を受ける形にしたか。
  - [ ] MIME type / サイズ / 拡張子の3検証をResource内で行うと決めたか。
  - [ ] upload失敗時はロールバック（保存ファイル削除）し、500 または 400 を返すと決めたか。
- **Source:**
  - `src/Resource/App/MediaUpload.php`
- **Tests:**
  - `tests/Resource/App/MediaUploadTest.php`
- **Key points:** `#[InputFile]` で `FileUpload|ErrorFileUpload` を受け、MIME/サイズ/拡張子検証後、`move()` で保存→メタデータ登録。失敗時はロールバック。
- **Do not:** 検証前にファイルを保存しない。メタデータ登録失敗時に保存ファイルを残さない。
- **マスター確認（After）:**
  - [ ] 不正MIME / 超過サイズ / 空ファイル が 400 で拒否される。
  - [ ] 正常アップロードで 201 + Location が返ることを `MediaUploadTest.php` 相当で green。

### `crawl-data-loader`

**`#[Link(crawl:...)]` + DataLoaderでN+1を解消する**

- **ID:** `crawl-data-loader`
- **Aliases:** crawl, linkCrawl, DataLoader, DataLoaderInterface, N+1, batch query, resource graph
- **Status:** `showcase`
- **Use when:** `#[Link(crawl:...)]`でリソースグラフを構築し、子リソースのN+1クエリをバッチで解消したい。
- **着手前チェック（Before）:**
  - [ ] `#[Link(crawl: ...)]` でcrawl名を指定し、リソースグラフを宣言的に構築すると決めたか。
  - [ ] N+1が起きる子リソースに `dataLoader: DataLoaderClass::class` を指定し、`DataLoaderInterface::__invoke()` でバッチクエリを実装すると決めたか。
- **Source:**
  - `src/Resource/App/Crawl/Articles.php`
  - `src/Resource/App/Crawl/Tags.php`
  - `src/DataLoader/ArticleTagsDataLoader.php`
- **Tests:**
  - `tests/Resource/App/Crawl/CrawlDataLoaderTest.php`
- **Key points:** `#[Link(crawl: ...)]` でグラフ名を宣言。`DataLoaderInterface::__invoke(array $queries): array` でバッチクエリ。keyはURI templateから自動推論。
- **Do not:** DataLoaderを使わずに1件ずつクエリするN+1状態を放置しない。
- **マスター確認（After）:**
  - [ ] クエリ数が子リソース数に比例せず定数になることを `CrawlDataLoaderTest.php` 相当で green。

### `state-transition-resource`

**状態遷移を独立Resourceとして切り出す**

- **ID:** `state-transition-resource`
- **Aliases:** state machine, state transition, draft published, ArticlePublish, 409 Conflict, AffectedRows
- **Status:** `canonical`
- **Use when:** リソースの状態遷移（draft→published等）をフィールド編集(PUT)とは別のResourceとして切り出したい。
- **着手前チェック（Before）:**
  - [ ] フィールド編集と状態遷移を別Resourceに分け、遷移専用のURIを持たせると決めたか。
  - [ ] 既に目標状態にある場合は409 Conflictを返し、再実行を安全にすると決めたか。
  - [ ] `AffectedRows` で原子性を判定し、競合を検出すると理解したか。
- **Source:**
  - `src/Resource/App/ArticlePublish.php`
  - `src/Query/ArticleCommandInterface.php::publish()`
  - `var/db/sql/article_publish.sql`
- **Tests:**
  - `tests/Resource/App/ArticlePublishTest.php`
- **Key points:** 状態遷移は独立Resource（`ArticlePublish`）。既に目標状態なら409 Conflict。`AffectedRows::isAffected()` で原子性判定。
- **Do not:** フィールド編集のPUTに状態遷移を混ぜない。既に目標状態の再遷移を200で成功扱いしない。
- **マスター確認（After）:**
  - [ ] draft→published で 200 + publishedAt が返る。
  - [ ] 既に published の再publishで 409 が返ることを `ArticlePublishTest.php` 相当で green。

### `error-status-mapping`

**例外→HTTPステータスマッピングとエラーハンドリング**

- **ID:** `error-status-mapping`
- **Aliases:** error handling, exception handler, status mapping, vnd.error, error page, JsonSchemaRequestExceptionHandler, AppThrowableHandler
- **Status:** `canonical`
- **Use when:** 例外をHTTPステータスコードにマッピングし、カスタムエラーページとJSON Schema検証例外ハンドリングを提供したい。
- **着手前チェック（Before）:**
  - [ ] ドメイン例外を `ExceptionStatusMapper` でHTTPステータスにマッピングすると決めたか。
  - [ ] APIとHTMLで別のハンドラ（`AppThrowableHandler` / `HtmlThrowableHandler`）を使うと理解したか。
  - [ ] JSON Schema validationエラーを `JsonSchemaRequestExceptionHandler` で `ValidationException` に変換すると決めたか。
- **Source:**
  - `src/Provide/Error/ExceptionStatusMapper.php`
  - `src/Provide/Error/AppThrowableHandler.php`
  - `src/Provide/Error/HtmlThrowableHandler.php`
  - `src/Validation/JsonSchemaRequestExceptionHandler.php`
- **Tests:**
  - `tests/Provide/Error/ExceptionStatusMapperTest.php`
  - `tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`
- **Key points:** `ExceptionStatusMapper` でドメイン例外→HTTPステータス。APIは `AppThrowableHandler`、HTMLは `HtmlThrowableHandler`。JSON Schema検証エラーは `ValidationException` に変換。
- **Do not:** ドメイン例外をそのままthrowして框架に500を任せない。
- **マスター確認（After）:**
  - [ ] 各ドメイン例外が正しいステータスコードにマッピングされることを `ExceptionStatusMapperTest.php` 相当で green。

## HTML / Page

### `page-resource-qiq-detail`

**Page Resourceで1件詳細HTMLを描画する**

- **ID:** `page-resource-qiq-detail`
- **Aliases:** Page Resource, Qiq, HTML detail, template variables, article page
- **Status:** `canonical`
- **Use when:** App Resourceとは別に、HTML表示用のPage Resourceを作りたい。
- **着手前チェック（Before）:**
  - [ ] App Resource（API）とは別にPage Resource（HTML）を分けると決めたか。
  - [ ] 表示に必要な値はPage Resourceで body に置き、Qiq template は描画に集中させると理解したか。
- **Source:**
  - `src/Resource/Page/Article.php::onGet()`
  - `templates/Page/Article.php`
  - `src/Renderer/CmsQiqRenderer.php`
- **Tests:**
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** Page Resourceが表示に必要なEntityや値をbodyに置く。Qiq templateは描画に集中する。
- **Do not:** templateからQuery Interfaceを呼ばない。
- **マスター確認（After）:**
  - [ ] template に Query Interface / DB 呼び出しが無い（`grep -i query templates/Page/<Name>.php` が空）。
  - [ ] Page Resource が描画に必要な値を body へ用意している。
  - [ ] `Resource/Page/ArticleTest.php` 相当でHTML描画と200を green。

### `page-resource-list`

**Page Resourceで一覧HTMLを描画する**

- **ID:** `page-resource-list`
- **Aliases:** HTML list, page list, Qiq list, pager HTML, filter page
- **Status:** `canonical`
- **Use when:** 絞り込みやpager付きの一覧HTMLを表示したい。
- **着手前チェック（Before）:**
  - [ ] list query・filter状態・pager表示用の値を Page Resource 側で準備すると決めたか。
  - [ ] template側でDB fetchやfilter解決をしないと理解したか。
- **Source:**
  - `src/Resource/Page/ArticleList.php::onGet()`
  - `templates/Page/ArticleList.php`
  - `src/Resource/Page/CategoryList.php::onGet()`
  - `src/Resource/Page/TagList.php::onGet()`
- **Tests:**
  - `tests/Resource/Page/ArticleListTest.php`
  - `tests/Resource/Page/CategoryListTest.php`
  - `tests/Resource/Page/TagListTest.php`
- **Key points:** list query、filter状態、pager表示用値をPage Resourceで準備する。
- **Do not:** template側でDB fetchや複雑なfilter解決を行わない。
- **マスター確認（After）:**
  - [ ] template に DB fetch / filter解決ロジックが無い。
  - [ ] public一覧は published のみに制限されている（リーダー向けページの不変条件）。
  - [ ] filter適用の一覧を `ArticleListTest.php` 相当で green。

### `markdown-to-html`

**Markdown本文をHTMLへ変換する**

- **ID:** `markdown-to-html`
- **Aliases:** Markdown, CommonMark, bodyHtml, renderer service, HTML conversion
- **Status:** `canonical`
- **Use when:** EntityのMarkdown本文をHTML templateに渡す前に変換したい。
- **着手前チェック（Before）:**
  - [ ] 変換を interface（`MarkdownRendererInterface`）越しにDI注入すると決めたか。
  - [ ] 変換結果（`bodyHtml`）は Page Resource で作り、template内でparserを生成しないと理解したか。
- **Source:**
  - `src/Resource/Page/Article.php::onGet()`
  - `src/Service/MarkdownRendererInterface.php`
  - `src/Service/CommonMarkRenderer.php`
  - `src/Provider/CommonMarkConverterProvider.php`
- **Tests:**
  - `tests/Resource/Page/ArticleTest.php`
- **Key points:** 変換サービスはDIで注入し、Page Resourceで `bodyHtml` を作る。
- **Do not:** template内でMarkdown parserを生成しない。
- **マスター確認（After）:**
  - [ ] 変換が interface 経由でDI注入され、template に `new` parser が無い。
  - [ ] `bodyHtml` がResource側で生成され、`ArticleTest.php` 相当で変換結果を green。

### `admin-prg-form`

**Admin formでPRGを使う**

- **ID:** `admin-prg-form`
- **Aliases:** admin form, PRG, Post Redirect Get, form validation, author scoped admin, write UI
- **Status:** `showcase`
- **Use when:** HTML formからApp Resourceのwrite APIを呼び、成功時にredirectしたい。
- **着手前チェック（Before）:**
  - [ ] Page Admin が write rules を再実装せず、App Resource の write API を**包む**だけにすると決めたか。
  - [ ] 成功時は303 redirect（Post/Redirect/Get）にすると決めたか。
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
- **Key points:** Page Adminは `$this->resource->post/put/delete('app://self/article', ...)` でApp Resourceを包み、成功時は303 redirect。
- **Do not:** Page AdminとApp Resourceのwrite rulesを別々に二重実装しない。
- **マスター確認（After）:**
  - [ ] Page Admin が `app://self/...` の write を呼び、独自のSQL/write logicを持たない。
  - [ ] 成功時に303 redirect している。
  - [ ] 他authorの記事を操作できないことを `AuthBoundaryTest.php` 相当で green。



### `admin-auth-boundary`

**AdminGuardによるauthor-scoped認可境界**

- **ID:** `admin-auth-boundary`
- **Aliases:** AdminGuard, auth boundary, author-scoped, authorization, session identity, admin page protection
- **Status:** `showcase`
- **Use when:** Admin Page Resourceで、ログイン済みユーザーが自分の記事のみ操作できる認可境界を設けたい。
- **着手前チェック（Before）:**
  - [ ] `AdminGuard` で `AdminUserInterface` を注入し、author identityをsessionから取得すると決めたか。
  - [ ] 他authorの記事を操作しようとした場合、403 Forbiddenを返すと決めたか。
- **Source:**
  - `src/Auth/AdminGuard.php`
  - `src/Auth/AdminUserInterface.php`
  - `src/Provider/AdminUserProvider.php`
- **Tests:**
  - `tests/Resource/Page/Admin/AuthBoundaryTest.php`
- **Key points:** `AdminGuard` でauthor-scoped認可。他authorのリソース操作は403。
- **Do not:** 認可チェックをResource本体に散らさない（guardで一元化）。
- **マスター確認（After）:**
  - [ ] 他authorの記事を操作できないことを `AuthBoundaryTest.php` 相当で green。

## Runtime / representation

### `stream-response`

**ファイルやバイナリをストリームで返す**

- **ID:** `stream-response`
- **Aliases:** streaming, stream response, file download, binary response, BEAR.Streamer, `StreamTransferInject`, `Content-Disposition`
- **Status:** `showcase`
- **Use when:** JSONではなく、ファイル本体や大きなレスポンスを返したい。
- **着手前チェック（Before）:**
  - [ ] `StreamTransferInject` を使い、open stream resource を `$this->body` に置くと決めたか。
  - [ ] `Content-Type` / `Content-Length` / `Content-Disposition` を明示すると決めたか。
  - [ ] stream success body には `#[JsonSchema]` を付けないと理解したか。
- **Source:**
  - `src/Resource/App/Variations/MediaStream.php::onGet()`
  - `src/Query/MediaQueryInterface.php::item()`
  - `src/Entity/Media.php`
  - `var/media/media-005.svg`
- **Tests:**
  - `tests/Resource/App/Variations/MediaStreamTest.php`
- **Key points:** `StreamTransferInject` を使い、`Content-Type`, `Content-Length`, `Content-Disposition` を明示し、open stream resourceを `$this->body` に置く。
- **Do not:** success stream bodyに `#[JsonSchema]` を付けない。ファイルopenをtemplateに置かない。
- **マスター確認（After）:**
  - [ ] Resource が `StreamTransferInject` を使い、body が stream resource。
  - [ ] 3つのheader（Type/Length/Disposition）がセットされる。
  - [ ] stream method に `#[JsonSchema]` が無いことを確認し、`MediaStreamTest.php` 相当で green。

### `cacheable-leaf`

**`#[Cacheable]` だけのleaf resourceを作る**

- **ID:** `cacheable-leaf`
- **Aliases:** cache leaf, `#[Cacheable]`, self URI tag, auto purge, QueryRepository cache
- **Status:** `showcase`
- **Use when:** 単体ResourceのGET/PUTで、利用者コードなしにキャッシュと同一URI purgeを示したい。
- **着手前チェック（Before）:**
  - [ ] cache surface を class-level `#[Cacheable]` だけで表し、manual cache primitive をResourceに出さないと決めたか。
- **Source:**
  - `src/Resource/App/Cache/Author.php`
  - `src/Resource/App/Cache/Tag.php`
- **Tests:**
  - `tests/Resource/App/Cache/AuthorCacheTest.php`
  - `tests/Resource/App/CacheTest.php`
- **Key points:** class-level `#[Cacheable]` がcache surface。manual cache primitiveはResourceに出さない。
- **Do not:** leaf resourceに不要な `Surrogate-Key` 手書きコードを足さない。
- **マスター確認（After）:**
  - [ ] class に `#[Cacheable]` があり、body生成に手書きの `Surrogate-Key` / cache primitive が無い。
  - [ ] PUT後にGETが更新され同一URIがpurgeされることを `AuthorCacheTest.php` 相当で green。

### `cache-embed-dependency`

**`#[Embed]` 親Resourceの依存を自動合成する**

- **ID:** `cache-embed-dependency`
- **Aliases:** cache parent, embed dependency, auto dependency, ETag dependency, AuthorProfile
- **Status:** `showcase`
- **Use when:** 親Resourceが子Resourceをembedし、子の更新で親cacheも無効化したい。
- **着手前チェック（Before）:**
  - [ ] 単一の子依存は `#[Embed]` で表し、`fromAssoc()` を使わないと決めたか。
  - [ ] 親はmanual cache codeを持たず、QueryRepositoryが子URI tagを親へmergeする前提を理解したか。
- **Source:**
  - `src/Resource/App/Cache/AuthorProfile.php`
  - `src/Resource/App/Cache/Author.php`
- **Tests:**
  - `tests/Resource/App/Cache/AuthorProfileCacheTest.php`
  - `tests/Resource/App/CacheTest.php`
- **Key points:** `#[Embed]` 子のURI tagをQueryRepositoryが親へmergeする。親Resourceはmanual cache codeを持たない。
- **Do not:** `#[Embed]` で表せる単一子依存に `fromAssoc()` を使わない。
- **マスター確認（After）:**
  - [ ] 親Resourceが `#[Embed]` で子を持ち、cache依存の手書きコードが無い。
  - [ ] 子の更新で親cacheが無効化されることを `AuthorProfileCacheTest.php` 相当で green。

### `cache-body-derived-dependency`

**body由来の可変長依存を `fromAssoc()` で宣言する**

- **ID:** `cache-body-derived-dependency`
- **Aliases:** variable dependencies, body-derived dependency, `UriTagInterface::fromAssoc`, surrogate key, article tags cache
- **Status:** `showcase`
- **Use when:** DB結果から得たN個の子URIに依存するResourceをcacheしたい。
- **着手前チェック（Before）:**
  - [ ] 依存先が**可変長**（N個）で静的 `#[Embed]` では表せないことを確認したか。
  - [ ] 唯一のmanual cache primitiveを `UriTagInterface::fromAssoc(...)` に限定すると決めたか。
- **Source:**
  - `src/Resource/App/Cache/ArticleTags.php`
  - `src/Resource/App/Cache/Tag.php`
- **Tests:**
  - `tests/Resource/App/Cache/ArticleTagsCacheTest.php`
  - `tests/Resource/App/CacheTest.php`
- **Key points:** `UriTagInterface::fromAssoc('app://self/cache/tag{?id}', $items)` が唯一のmanual cache primitive。
- **Do not:** 可変長依存を静的 `#[Embed]` で無理に表現しない。空tag headerをセットしない。
- **マスター確認（After）:**
  - [ ] 依存宣言が `fromAssoc()` 1箇所に集約され、空の時はheaderをセットしない。
  - [ ] 子tagの1つを更新すると当該親cacheだけが無効化されることを `ArticleTagsCacheTest.php` 相当で green。

### `async-embed-parallel`

**embed graphを並列実行に載せる**

- **ID:** `async-embed-parallel`
- **Aliases:** async, parallel embed, BEAR.Async, ext-parallel, Swoole, embedded resources
- **Status:** `showcase`
- **Use when:** 既存Resourceの `#[Embed]` graphを変更せずに、runtime overlayで並列化したい。
- **着手前チェック（Before）:**
  - [ ] Resource code は通常の `#[Embed]` のまま変えず、並列化は runtime/context 側のmodule overlayで行うと理解したか。
  - [ ] 実行に ext-parallel + ZTS PHP（または Swoole）が必要な前提を確認したか。
- **Source:**
  - `bin/async.php`
  - `src/Resource/App/Article.php::onGet()`
  - `docker-compose.yml`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** Resource codeは通常の `#[Embed]` のまま。runtime/context側でparallel moduleを重ねる。
- **Do not:** 並列化のためにResource body assemblyを別物に書き換えない。
- **マスター確認（After）:**
  - [ ] sync版と並列版で Resource クラスの差分がゼロ（並列化はcontext側のみ）。
  - [ ] 並列版でも同じHAL envelopeが出ることを `HalEnvelopeContractTest.php` 相当で green。

### `cli-resource`

**ResourceをCLIコマンドとして公開する**

- **ID:** `cli-resource`
- **Aliases:** CLI, `#[Cli]`, `#[Option]`, bear-cli-gen, article-show, article-list
- **Status:** `showcase`
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
  - `tests/Smoke/MediaQuerySmokeTest.php`
- **Key points:** Resource methodに `#[Cli]` と `#[Option]` を付け、`composer cli` で生成する。
- **Do not:** CLI用に別のapplication serviceを重複実装しない。
- **マスター確認（After）:**
  - [ ] CLI が既存Resource methodを呼び、ロジックの重複実装が無い。
  - [ ] `composer cli` 後に `bin/cli/<name>` が生成され、HTTPと同じ結果を返す。



### `csrf-same-origin-protection`

**CSRFトークン + Same-Origin interceptorをAOPでbindする**

- **ID:** `csrf-same-origin-protection`
- **Aliases:** CSRF, CsrfToken, SameOrigin, interceptor, AOP, form protection, double submit cookie
- **Status:** `canonical`
- **Use when:** Admin Page Resourceのwrite操作をCSRF攻撃とCross-Site Origin攻撃から保護したい。
- **着手前チェック（Before）:**
  - [ ] `#[CsrfToken]` と `#[SameOrigin]` の2つのAttributeを使い、それぞれ interceptor をAOP bindすると決めたか。
  - [ ] CSRFトークンはdouble-submit-cookie方式で、session経由で生成・検証すると理解したか。
  - [ ] Same-Originは `Sec-Fetch-Site` / `Origin` / `Referer` の3シグナルで判定し、全欠落時はfail-closedにすると理解したか。
- **Source:**
  - `src-csrf/Attribute/CsrfToken.php`
  - `src-csrf/Attribute/SameOrigin.php`
  - `src-csrf/Interceptor/CsrfTokenInterceptor.php`
  - `src-csrf/Interceptor/SameOriginInterceptor.php`
  - `src-csrf/CsrfModule.php`
  - `src-csrf/SessionCsrfToken.php`
- **Tests:**
  - `tests/Interceptor/CsrfTokenInterceptorTest.php`
  - `tests/Interceptor/CsrfTokenWiringTest.php`
  - `tests/Interceptor/SameOriginInterceptorTest.php`
  - `tests/Interceptor/SameOriginWiringTest.php`
- **Key points:** `#[CsrfToken]` → double-submit-cookie CSRF検証。`#[SameOrigin]` → Sec-Fetch-Site/Origin/Referer 3シグナル判定、fail-closed。
- **Do not:** シグナル全欠落時に許可しない（fail-closed）。CSRFトークンをURLに露出させない。
- **マスター確認（After）:**
  - [ ] token mismatch で `ForbiddenException` が throw される。
  - [ ] origin mismatch で `ForbiddenException` が throw される。
  - [ ] `CsrfTokenInterceptorTest.php` / `SameOriginInterceptorTest.php` 相当で green。

### `cache-purge`

**`#[Purge]`でwrite時にcollection cacheを手動無効化する**

- **ID:** `cache-purge`
- **Aliases:** Purge, cache invalidation, manual purge, collection cache, #[Purge]
- **Status:** `showcase`
- **Use when:** write操作（POST/PUT/DELETE）後に、関連collection resourceのキャッシュを手動で無効化したい。
- **着手前チェック（Before）:**
  - [ ] `#[Purge(uri: 'app://self/<collection>')]` をwrite methodに付け、該当collection cacheを一括無効化すると決めたか。
  - [ ] Purge対象はcollection URI（`articles`, `categories`）で、item URIでないことを確認したか。
- **Source:**
  - `src/Resource/App/Article.php::onPost()`
  - `src/Resource/App/Article.php::onPut()`
  - `src/Resource/App/Article.php::onDelete()`
  - `src/Resource/App/Category.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/App/CategoryTest.php`
- **Key points:** `#[Purge(uri: 'app://self/articles')]` をwrite methodに付ける。collection全体のキャッシュが無効化される。
- **Do not:** item URIではなくcollection URIをPurgeする。write後にPurgeを忘れない。
- **マスター確認（After）:**
  - [ ] POST/PUT/DELETE 後に該当collection cacheが無効化されることを確認。

### `donut-cache`

**`#[DonutCache]`で部分キャッシュを示す**

- **ID:** `donut-cache`
- **Aliases:** DonutCache, donut caching, partial cache, donut hole, ArticlePreview
- **Status:** `showcase`
- **Use when:** Resource全体のうち、embedされた非キャッシュ可能部分を除いたキャッシュ可能部分を分離してキャッシュしたい。
- **着手前チェック（Before）:**
  - [ ] `#[DonutCache]` は全体キャッシュではなく、embed子が非キャッシュ可能な時に使うと理解したか。
  - [ ] `#[Cacheable]` とは使い分け（DonutCache=部分キャッシュ、Cacheable=全体キャッシュ）を理解したか。
- **Source:**
  - `src/Resource/App/Cache/ArticlePreview.php`
- **Tests:**
  - `tests/Resource/App/CacheTest.php`
- **Key points:** `#[DonutCache]` はembed子が非キャッシュ可能な時の部分キャッシュ。全体キャッシュは `#[Cacheable]`。
- **Do not:** `#[Cacheable]` と `#[DonutCache]` を混同しない。
- **マスター確認（After）:**
  - [ ] `#[DonutCache]` Resource がキャッシュされ、子の更新でhole部分のみ再描画されることを `CacheTest.php` 相当で green。

### `cacheable-response`

**`#[CacheableResponse]`でレスポンス全体をキャッシュする**

- **ID:** `cacheable-response`
- **Aliases:** CacheableResponse, response cache, whole content cache, Articles, Categories
- **Status:** `showcase`
- **Use when:** Collection resourceのレスポンス全体をキャッシュし、ETagと条件付きリクエストで配信したい。
- **着手前チェック（Before）:**
  - [ ] `#[CacheableResponse]` は全体コンテンツキャッシュ（embed子も含む）で、`#[Cacheable]` は個別Resourceのキャッシュだと理解したか。
  - [ ] Donut cacheと違い、embed子もキャッシュ対象になることを理解したか。
- **Source:**
  - `src/Resource/App/Articles.php`
  - `src/Resource/App/Categories.php`
- **Tests:**
  - `tests/Resource/App/ArticlesTest.php`
  - `tests/Resource/App/CategoryTest.php`
- **Key points:** `#[CacheableResponse]` は全体キャッシュ。embed子も含めてキャッシュされる。`#[Cacheable]`（個別キャッシュ）と `#[DonutCache]`（部分キャッシュ）と使い分ける。
- **Do not:** 3つのcache属性（Cacheable / DonutCache / CacheableResponse）を混同しない。
- **マスター確認（After）:**
  - [ ] `#[CacheableResponse]` Resource が全体キャッシュされ、ETagが付くことを確認。

### `import-app`

**ImportAppModuleで他アプリのResourceを呼ぶ**

- **ID:** `import-app`
- **Aliases:** ImportApp, ImportAppModule, multi-app, composer package, cross-app resource, System Boundary
- **Status:** `showcase`
- **Use when:** composer installした別アプリのResourceを、自アプリから `app://<host>/...` で呼びたい。
- **着手前チェック（Before）:**
  - [ ] `ImportAppModule` に `ImportApp($host, $namespace, $context)` を渡してinstallすると決めたか。
  - [ ] 呼び出し側は `app://<host>/<resource>` でアクセスし、`#[Embed]` / `#[Link]` も使えると理解したか。
- **Source:**
  - `tests/Example/ImportAppExampleTest.php`
- **Tests:**
  - `tests/Example/ImportAppExampleTest.php`
- **Key points:** `ImportAppModule` で他アプリをimport。host名経由でresource呼び出し。`#[Embed]` / `#[Link]` も使用可能。
- **Do not:** マイクロサービス化せずとも、composer経由でアプリ間連携が可能。
- **マスター確認（After）:**
  - [ ] 他アプリのresourceが `app://<host>/...` で呼べることを `ImportAppExampleTest.php` 相当で green。



## Event Sourcing

### `event-extraction`

**Semantic Logger観察ログからimmutable Eventを抽出する**

- **ID:** `event-extraction`
- **Aliases:** event sourcing, SemanticLogExtractor, Event, RecordedMethods, semantic logger, event extraction, observation
- **Status:** `showcase`
- **Use when:** アプリケーションの状態変化をイベントとして記録し、replay可能なsource of truthにしたい。
- **着手前チェック（Before）:**
  - [ ] Semantic Loggerのopen/close観察ツリーからEventを抽出し、ドメインにevent-dispatchコードを追加しないと理解したか。
  - [ ] `RecordedMethods` で記録対象method（デフォルト: POST/PUT/PATCH/DELETE、GETは除外）を制御すると決めたか。
  - [ ] Eventは `uri`, `method`, `params`, `timestamp`, `result` の事実のみを持つと理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/SemanticLogExtractor.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Event.php` *(external package)*
  - `vendor/bear/event-sourcing/src/RecordedMethods.php` *(external package)*
  - `tests/Fake/FakeResourceRequestContext.php`
  - `tests/Fake/FakeResourceResponseContext.php`
- **Tests:**
  - `tests/Smoke/EventExtractionTest.php`
- **Key points:** Semantic Loggerが観察源。Eventはresource操作（method on uri）の不変事実。`RecordedMethods` で記録範囲を制御。code >= 400 はスキップ。
- **Do not:** ドメインコードにevent-dispatchを追加しない。Eventにドメインロジックを入れない。
- **マスター確認（After）:**
  - [ ] write操作（POST/PUT/PATCH/DELETE）のみがデフォルトで抽出される。
  - [ ] `RecordedMethods::WITH_READS` でGETも含まれる。
  - [ ] code >= 400 の操作はスキップされることを `EventExtractionTest.php` 相当で green。

### `event-filter-replay`

**Eventsコレクションをフィルタしてreplayする**

- **ID:** `event-filter-replay`
- **Aliases:** event replay, filter events, CallbackFilterIterator, Events, replay, projection
- **Status:** `showcase`
- **Use when:** 抽出したEventをURI prefix / params / timestampでフィルタし、特定エンティティの状態変化をreplayしたい。
- **着手前チェック（Before）:**
  - [ ] `Events` はcountable + iterableで、PHP標準の `CallbackFilterIterator` でフィルタすると理解したか。
  - [ ] query methodをEventsに追加せず、filterをstackすると理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/Events.php` *(external package)*
  - `vendor/bear/event-sourcing/src/EventsInterface.php` *(external package)*
- **Tests:**
  - `tests/Smoke/EventReplayTest.php`
- **Key points:** `CallbackFilterIterator` でURI prefix / params / timestamp / method でフィルタ。query methodを生やさずfilterをstack。
- **Do not:** Eventsコレクションに専用query methodを追加しない（PHP標準iteratorで十分）。
- **マスター確認（After）:**
  - [ ] 特定id / URI prefix / method でフィルタされたEventが正しく抽出されることを `EventReplayTest.php` 相当で green。

### `event-store-persistence`

**EventStoreInterfaceでEventを永続化する**

- **ID:** `event-store-persistence`
- **Aliases:** EventStore, InMemoryEventStore, MediaQueryEventStore, event persistence, event storage
- **Status:** `support`
- **Use when:** 抽出したEventを永続化し、後から全Eventを再取得したい。
- **着手前チェック（Before）:**
  - [ ] `EventStoreInterface` は `append`, `appendAll`, `all` の小さい永続化ポートで、runtime hookではないと理解したか。
  - [ ] test用は `InMemoryEventStore`、SQL永続化は `MediaQueryEventStore`（Ray.MediaQuery経由）を使うと決めたか。
  - [ ] ES Moduleはアプリ所有のMediaQuery/AuraSqlを隠さないと理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/EventStoreInterface.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Store/InMemoryEventStore.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Store/MediaQueryEventStore.php` *(external package)*
- **Tests:**
  - `tests/Smoke/EventStoreTest.php`
- **Key points:** `EventStoreInterface` は小さい永続化ポート。InMemory（test）とMediaQuery（SQL）の2実装。ES ModuleはアプリのDB設定を隠さない。
- **Do not:** runtime中の自動永続化をしない（明示的に `appendAll()` を呼ぶ）。
- **マスター確認（After）:**
  - [ ] InMemoryEventStore に appendAll → all で同じEventが戻ることを `EventStoreTest.php` 相当で green。

### `resource-observation-bridge`

**BEAR.Resource実行からSemantic Logger観察ログを生成する**

- **ID:** `resource-observation-bridge`
- **Aliases:** ResourceObservationModule, InvokerInterface, BodyStoreInterface, FileBodyStore, DevLogModule, observation bridge
- **Status:** `showcase`
- **Use when:** BEAR.Resourceの実行ツリーをSemantic Logger観察ログとして記録し、event extractionの入力にしたい。
- **着手前チェック（Before）:**
  - [ ] `ResourceObservationModule` で `InvokerInterface` をdecorateし、`LoggerInterface` はdecorateしないと理解したか。
  - [ ] `BodyStoreInterface` でrendered bodyを外部化し、`body_ref` で参照すると決めたか。
  - [ ] 開発時は `DevLogModule` でbodyファイルを自動クリア＋全method記録すると理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/Resource/ResourceObservationModule.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Resource/BodyStoreInterface.php` *(external package)*
  - `vendor/bear/event-sourcing/src/Resource/NullBodyStore.php` *(external package)*
  - `tests/Fake/Observation/Resource/App/Hello.php`
- **Tests:**
  - `tests/Smoke/ResourceObservationTest.php`
- **Key points:** `InvokerInterface` decorate で観察ログ生成。`BodyStoreInterface` でbody外部化。`DevLogModule` は開発用（全method記録＋自動クリア）。
- **Do not:** `LoggerInterface` をdecorateしない（`InvokerInterface` が正しいdecorate対象）。
- **マスター確認（After）:**
  - [ ] Resource実行後にSemantic Loggerログが生成され、Event抽出可能になることを `ResourceObservationTest.php` 相当で green。

## Deferred execution

### `defer-resource-request`

**`#[Defer]` + `#[Link]`で応答後に実行するfollow-up Resourceを宣言する**

- **ID:** `defer-resource-request`
- **Aliases:** defer, deferred, #[Defer], 202 Accepted, post-response execution, DeferModule, DeferInterceptor, SyncDefer
- **Status:** `showcase`
- **Use when:** Resourceが202 Acceptedを即時返却し、重いfollow-up処理をレスポンス転送後に実行したい。
- **着手前チェック（Before）:**
  - [ ] `#[Defer(['rel1', 'rel2'])]` で `#[Link]` relを指定し、hardcoded URIを使わないと決めたか。
  - [ ] follow-up Resourceは通常のResourceであり、deferを意識しないと理解したか。
  - [ ] 実行戦略（sync/queue/Swoole）は `DeferInterface` bindingで切り替え、Resource codeは変えないと理解したか。
- **Source:**
  - `vendor/bear/defer/src/Attribute/Defer.php` *(external package)*
  - `vendor/bear/defer/src/DeferInterceptor.php` *(external package)*
  - `vendor/bear/defer/src/Module/DeferModule.php` *(external package)*
  - `vendor/bear/defer/src/SyncDefer.php` *(external package)*
  - `tests/Fake/Defer/Resource/App/Article.php`
  - `tests/Fake/Defer/Resource/App/Publish.php`
  - `tests/Fake/Defer/Resource/App/Note.php`
- **Tests:**
  - `tests/Resource/App/DeferTest.php`
- **Key points:** `#[Defer]` は `#[Link]` relを参照し、bodyからURI templateを展開。`DeferInterceptor` はAfter interceptor（proceed後にenqueue）。実行戦略はbindingで切り替え。
- **Do not:** Resource内でdefer callを手書きしない（`#[Defer]` で宣言的）。follow-up URIをhardcodeしない（`#[Link]` 経由）。
- **マスター確認（After）:**
  - [ ] 202 が即時返却され、follow-upが転送後に実行されることを `DeferTest.php` 相当で green。

### `defer-conditional`

**`DeferInterface::add()`で条件付きdeferを手動制御する**

- **ID:** `defer-conditional`
- **Aliases:** conditional defer, DeferInterface, manual defer, add(), flush(), DeferTransfer, ConnectionCloserInterface
- **Status:** `showcase`
- **Use when:** follow-up処理が条件付きの場合、`#[Defer]` を迂回して `DeferInterface::add()` で手動制御したい。
- **着手前チェック（Before）:**
  - [ ] `DeferInterface` と `ResourceInterface` をinjectし、`$defer->add($request)` で手動enqueueすると決めたか。
  - [ ] `DeferTransfer` がbase transfer後にconnectionをreleaseし、その後に `flush()` が走ることを理解したか。
- **Source:**
  - `vendor/bear/defer/src/DeferInterface.php` *(external package)*
  - `vendor/bear/defer/src/DeferTransfer.php` *(external package)*
  - `vendor/bear/defer/src/ConnectionCloserInterface.php` *(external package)*
  - `vendor/bear/defer/src/SapiConnectionCloser.php` *(external package)*
  - `tests/Fake/Defer/Resource/App/ConditionalArticle.php`
- **Tests:**
  - `tests/Resource/App/DeferTest.php`
- **Key points:** `DeferInterface::add(callable $request)` で手動enqueue。`DeferTransfer` は transfer → connection release → flush の順。`ConnectionCloserInterface` でSAPI別の接続解放。
- **Do not:** `DeferInterface` のsingleton queueをflushせずに放置しない（`flush()` はrequest boundaryで必須）。
- **マスター確認（After）:**
  - [ ] 条件付きでfollow-upがenqueueされ、転送後に実行されることを `DeferTest.php` 相当で green。

## Tests / fake

### `fake-sql-query`

**DBなしでMediaQueryをFakeする**

- **ID:** `fake-sql-query`
- **Aliases:** FakeSqlQuery, no DB test, fake context, test context, hermetic tests
- **Status:** `support`
- **Use when:** DBなしでResource、Query、write flowをテストしたい。
- **着手前チェック（Before）:**
  - [ ] Fake は便利データではなく Query contract の実行可能な代替であると理解したか。
  - [ ] `DbQueryInterceptor` が `#[DbQuery]` を戻り型で getRow/getRowList に振り分ける前提を理解したか（write id も getRow/getRowList 側で扱う）。
- **Source:**
  - `tests/Fake/FakeSqlQuery.php`
  - `src/Module/FakeModule.php`
  - `src/Module/TestModule.php`
  - `var/fake/article.json`
- **Tests:**
  - `tests/Smoke/FakeSqlQueryTest.php`
  - `tests/Smoke/FakeEntityFetch.php`
  - `tests/Smoke/FakePostQueryRows.php`
- **Key points:** default PHPUnitはDBなしで動く。Fakeは便利データではなくQuery contractの実行可能な代替。
- **Do not:** すぐmockに逃げず、既存Fakeの意味を保つ。
- **マスター確認（After）:**
  - [ ] 主要テストがDBなしで green（`vendor/bin/phpunit`）。
  - [ ] Fake が read/write の両SQL idを Query contract 通りに扱う。
  - [ ] `FakeSqlQueryTest.php` 相当が green。

### `app-resource-test`

**App ResourceのAPI contractをテストする**

- **ID:** `app-resource-test`
- **Aliases:** resource test, API test, HAL JSON test, fake-hal-api-app, ResourceInterface
- **Status:** `support`
- **Use when:** App Resourceのstatus code、body shape、write flowを固定したい。
- **着手前チェック（Before）:**
  - [ ] client視点でResourceを呼び、status / body / schema / hypermedia の期待を pin すると決めたか。
  - [ ] private method単位ではなく Resource の contract をテスト対象にすると理解したか。
- **Source:**
  - `tests/AbstractAppTestCase.php`
  - `tests/Resource/App/ArticleTest.php`
  - `tests/Resource/App/ArticlesTest.php`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
- **Key points:** client視点でResourceを呼び、status/body/schema/hypermediaの期待を pin する。
- **Do not:** 実装内部のprivate method単位を主テストにしない。
- **マスター確認（After）:**
  - [ ] テストが `ResourceInterface` 経由でResourceを呼んでいる（内部privateを直接叩いていない）。
  - [ ] status code と body shape を pin し green。

### `page-resource-test`

**Page ResourceのHTML contractをテストする**

- **ID:** `page-resource-test`
- **Aliases:** page test, Qiq test, HTML resource test, html-test-hal-api-app
- **Status:** `support`
- **Use when:** Page Resourceとtemplateが期待するHTMLやstatusを固定したい。
- **着手前チェック（Before）:**
  - [ ] HTML context（`HtmlModule` + Fake）でDBなしに描画を検証すると決めたか。
  - [ ] 既定は `Visitor`、admin testでは fake admin session を明示注入すると理解したか。
- **Source:**
  - `tests/AbstractPageTestCase.php`
  - `tests/Resource/Page/ArticleTest.php`
  - `tests/Resource/Page/ArticleListTest.php`
- **Tests:**
  - `tests/Resource/Page/ArticleTest.php`
  - `tests/Resource/Page/IndexTest.php`
- **Key points:** HTML contextは `HtmlModule` とFakeを合成してDBなしで描画を検証する。
- **Do not:** Page testのためだけに実DBを必須にしない。
- **マスター確認（After）:**
  - [ ] Page test がDBなしで green。
  - [ ] HTML出力の要素・status を pin し `Resource/Page/ArticleTest.php` 相当が green。

### `hypermedia-workflow-test`

**Link/Embedを辿るworkflowをテストする**

- **ID:** `hypermedia-workflow-test`
- **Aliases:** hypermedia test, HAL workflow, follow links, `_links`, `_embedded`, rel naming
- **Status:** `support`
- **Use when:** API clientがHAL linkやembedを使って遷移できることを固定したい。
- **着手前チェック（Before）:**
  - [ ] Resource単体のbody assertだけでなく、rel を辿る遷移可能性をテストすると決めたか。
  - [ ] rel名の層分離（link=Choreography / embed=Taxonomy）を検証対象に含めると理解したか。
- **Source:**
  - `tests/Hypermedia/AbstractWorkflowTestCase.php`
  - `tests/Hypermedia/ReaderBrowsesByCategoryTest.php`
  - `tests/Hypermedia/ReaderBrowsesByTagTest.php`
  - `tests/Hypermedia/EditorManagesArticleTest.php`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** rel名の層分離、遷移可能性、HAL envelopeをclient視点で確認する。
- **Do not:** Resource単体のbody assertだけでhypermedia contractを済ませない。
- **マスター確認（After）:**
  - [ ] テストが `_links` / `_embedded` を実際に辿って次Resourceへ遷移している。
  - [ ] reader/editor の代表workflowが `HalEnvelopeContractTest.php` 相当で green。

### `mysql-integration-test`

**実DB経路を必要時だけ検証する**

- **ID:** `mysql-integration-test`
- **Aliases:** MySQL integration, real DB test, migrations, seed, skip when unavailable
- **Status:** `support`
- **Use when:** SQL、migration、real backendの代表経路を確認したい。
- **着手前チェック（Before）:**
  - [ ] default test suite は hermetic に保ち、MySQL integration は接続不可なら skip すると決めたか。
  - [ ] 全開発者にMySQL起動を必須にしないと理解したか。
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
- **Key points:** default test suiteはhermetic。MySQL integrationは接続不可ならskipする。
- **Do not:** 全開発者にMySQL起動を必須にしない。
- **マスター確認（After）:**
  - [ ] MySQL不在時にintegration testが fail ではなく skip する。
  - [ ] MySQL起動時に migration+seed 経由で代表CRUDが green。

## Semantic / generated artifacts

### `alps-profile-ssot`

**ALPS profileを意味のSSOTにする**

- **ID:** `alps-profile-ssot`
- **Aliases:** ALPS, semantic profile, ontology, taxonomy, choreography, SSOT, profile.json
- **Status:** `support`
- **Use when:** Resource名、rel名、入力語彙を意味モデルから揃えたい。
- **着手前チェック（Before）:**
  - [ ] 語彙の出所を `var/alps/profile.json`（SSOT）に一本化すると決めたか。
  - [ ] Ontology（語彙）/ Taxonomy（名詞）/ Choreography（遷移名）の3層を区別したか。
- **Source:**
  - `var/alps/profile.json`
  - `docs/alps.md`
  - `docs/architecture.md`
- **Tests:**
  - `tests/Hypermedia/HalEnvelopeContractTest.php`
- **Key points:** Ontologyは `articleTitle` などの語彙、Taxonomyは `Article` などの名詞、Choreographyは `goArticle` などの遷移名。
- **Do not:** HAL link relとembed relに同じ命名層を使わない。
- **マスター確認（After）:**
  - [ ] Resource名・rel名・入力語彙が profile.json の定義と一致。
  - [ ] link rel=Choreography / embed rel=Taxonomy の層分離が守られている。

### `semantic-fake-data`

**semantic-exで決定的fake dataを作る**

- **ID:** `semantic-fake-data`
- **Aliases:** fake data, semantic-ex, deterministic data, observations, seed source
- **Status:** `support`
- **Use when:** DBなしテストとreal DB seedの両方で使う代表データを生成したい。
- **着手前チェック（Before）:**
  - [ ] fake data を決定的（`mt_srand(42)`）かつ参照整合性ありで生成すると決めたか。
  - [ ] 同じfakeを no-DB テストと real seed の共通入力にすると理解したか。
- **Source:**
  - `bin/semantic-ex/gen-fake.php`
  - `var/fake/article.json`
  - `var/fake/author.json`
  - `var/fake/observations.md`
  - `bin/seed.php`
- **Tests:**
  - `tests/Smoke/FakeSqlQueryTest.php`
  - `tests/Smoke/SqlSmokeTest.php`
- **Key points:** fake dataは決定的で、参照整合性を持ち、Fakeとreal seedの共通入力になる。
- **Do not:** testごとに意味の違うfixtureを散らさない。
- **マスター確認（After）:**
  - [ ] `composer fake` を2回実行しても出力 `var/fake/*.json` が同一（決定的）。
  - [ ] 同じfakeで no-DB テストと seed が成立する。

### `json-schema-generated`

**fake observationからJSON Schemaを生成する**

- **ID:** `json-schema-generated`
- **Aliases:** generated schema, JSON Schema, semantic-ex constraints, response schema, validation schema
- **Status:** `support`
- **Use when:** 実例データから観察した制約をschemaとして固定したい。
- **着手前チェック（Before）:**
  - [ ] 制約を「前もって決める」のではなく fake observation から導出すると理解したか。
  - [ ] 生成した response schema / validation schema を Resource の `#[JsonSchema]` に接続すると決めたか。
- **Source:**
  - `bin/semantic-ex/gen-schemas.php`
  - `var/json_schema/article.json`
  - `var/json_schema/articleList.json`
  - `var/json_validate/article_create.json`
  - `var/json_validate/article_update.json`
- **Tests:**
  - `tests/Resource/App/ArticleTest.php`
  - `tests/params/sql_params.php`
- **Key points:** response schemaとinput validation schemaをResourceの `#[JsonSchema]` に接続する。
- **Do not:** Resource bodyを変えたのにschema更新を忘れない。
- **マスター確認（After）:**
  - [ ] `composer schema` でschemaが再生成され、Resourceの `#[JsonSchema]` 参照と一致。
  - [ ] body変更時にschema更新を伴い `ArticleTest.php` 相当が green。

### `apidoc-llms-generated`

**API docsとllms.txtを生成する**

- **ID:** `apidoc-llms-generated`
- **Aliases:** ApiDoc, OpenAPI, llms.txt, docs generation, `composer doc`, API documentation
- **Status:** `support`
- **Use when:** Resource、schema、ALPSから人間向け・AI向けのAPI資料を生成したい。
- **着手前チェック（Before）:**
  - [ ] ドキュメントを手書きで固定せず、Resource/schema/ALPS から生成すると決めたか。
  - [ ] AI向け入口が `docs/llms.txt`、人間向けが `docs/index.html` / `docs/openapi.json` だと理解したか。
- **Source:**
  - `apidoc.xml`
  - `docs/openapi.json`
  - `docs/llms.txt`
  - `docs/index.html`
  - `composer.json`
- **Tests:**
  - `tests/Smoke/MediaQuerySmokeTest.php`
- **Key points:** `composer doc` がApiDocとALPS HTMLを生成する。AI向けには `docs/llms.txt` が入口になる。
- **Do not:** 手書きドキュメントだけを正とし、Resourceやschemaとの同期を失わない。
- **マスター確認（After）:**
  - [ ] `composer doc` でAPI資料が再生成され、Resource/schemaと同期する。
  - [ ] 生成物（openapi.json / llms.txt）が現在のルートとResponseを反映している。
