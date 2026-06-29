# BEAR.Kata — New Kata Backlog

Generated from a full audit of BEAR.Kata (implemented-but-undocumented
patterns), BEAR.EventSourcing, and BEAR.Defer. Each entry follows the
existing Kata format: ID, Aliases, Status, Use when, Before checklist,
Source, Tests, Key points, Do not, After checklist.

Status legend — same as `docs/source-index.md`:

| Status | Meaning |
|---|---|
| `canonical` | Default first-copy reference form |
| `showcase` | Isolated feature demonstration |
| `support`   | Test / fake / generation support |

---

## A. Already implemented in BEAR.Kata — Kata entry missing (11)

These have source code and tests in the repo today. Adding the Kata
entry to `docs/source-index.md` completes them; no new code is needed.

---

### A1 `auth-oauth-flow`

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

---

### A2 `csrf-same-origin-protection`

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

---

### A3 `file-upload-input`

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
  - `src/Provider/CommonMarkConverterProvider.php`（参照のみ）
- **Tests:**
  - `tests/Resource/App/MediaUploadTest.php`
- **Key points:** `#[InputFile]` で `FileUpload|ErrorFileUpload` を受け、MIME/サイズ/拡張子検証後、`move()` で保存→メタデータ登録。失敗時はロールバック。
- **Do not:** 検証前にファイルを保存しない。メタデータ登録失敗時に保存ファイルを残さない。
- **マスター確認（After）:**
  - [ ] 不正MIME / 超過サイズ / 空ファイル が 400 で拒否される。
  - [ ] 正常アップロードで 201 + Location が返ることを `MediaUploadTest.php` 相当で green。

---

### A4 `crawl-data-loader`

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

---

### A5 `state-transition-resource`

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

---

### A6 `error-status-mapping`

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

---

### A7 `cache-purge`

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

---

### A8 `donut-cache`

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

---

### A9 `cacheable-response`

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

---

### A10 `admin-auth-boundary`

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

---

### A11 `import-app`

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

---

## B. BEAR.EventSourcing — new patterns (4)

Requires `composer require bear/event-sourcing`. Example resources
and tests will be created in BEAR.Kata to demonstrate each pattern.

---

### B1 `event-extraction`

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
  - `vendor/bear/event-sourcing/src/SemanticLogExtractor.php`
  - `vendor/bear/event-sourcing/src/Event.php`
  - `vendor/bear/event-sourcing/src/RecordedMethods.php`
  - `vendor/bear/event-sourcing/examples/extract.php`
- **Tests:**
  - `tests/Smoke/EventExtractionTest.php`（新規）
- **Key points:** Semantic Loggerが観察源。Eventはresource操作（method on uri）の不変事実。`RecordedMethods` で記録範囲を制御。
- **Do not:** ドメインコードにevent-dispatchを追加しない。Eventにドメインロジックを入れない。
- **マスター確認（After）:**
  - [ ] Semantic LoggerログからEventが抽出され、`method`, `uri`, `params`, `timestamp` が正しいことを green。

---

### B2 `event-filter-replay`

**Eventsコレクションをフィルタしてreplayする**

- **ID:** `event-filter-replay`
- **Aliases:** event replay, filter events, CallbackFilterIterator, Events, replay, projection
- **Status:** `showcase`
- **Use when:** 抽出したEventをURI prefix / params / timestampでフィルタし、特定エンティティの状態変化をreplayしたい。
- **着手前チェック（Before）:**
  - [ ] `Events` はcountable + iterableで、PHP標準の `CallbackFilterIterator` でフィルタすると理解したか。
  - [ ] query methodをEventsに追加せず、filterをstackすると理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/Events.php`
  - `vendor/bear/event-sourcing/src/EventsInterface.php`
  - `vendor/bear/event-sourcing/examples/replay.php`
- **Tests:**
  - `tests/Smoke/EventReplayTest.php`（新規）
- **Key points:** `CallbackFilterIterator` でURI prefix / params / timestamp / method でフィルタ。query methodを生やさずfilterをstack。
- **Do not:** Eventsコレクションに専用query methodを追加しない（PHP標準iteratorで十分）。
- **マスター確認（After）:**
  - [ ] 特定idのEventがフィルタされ、writeのみが抽出されることを green。

---

### B3 `event-store-persistence`

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
  - `vendor/bear/event-sourcing/src/EventStoreInterface.php`
  - `vendor/bear/event-sourcing/src/Store/InMemoryEventStore.php`
  - `vendor/bear/event-sourcing/src/Store/MediaQueryEventStore.php`
  - `vendor/bear/event-sourcing/src/Module/MediaQueryEventStoreModule.php`
- **Tests:**
  - `tests/Smoke/EventStoreTest.php`（新規）
- **Key points:** `EventStoreInterface` は小さい永続化ポート。InMemory（test）とMediaQuery（SQL）の2実装。ES ModuleはアプリのDB設定を隠さない。
- **Do not:** runtime中の自動永続化をしない（明示的に `appendAll()` を呼ぶ）。
- **マスター確認（After）:**
  - [ ] InMemoryEventStore に appendAll → all で同じEventが戻ることを green。

---

### B4 `resource-observation-bridge`

**BEAR.Resource実行からSemantic Logger観察ログを生成する**

- **ID:** `resource-observation-bridge`
- **Aliases:** ResourceObservationModule, InvokerInterface, BodyStoreInterface, FileBodyStore, DevLogModule, observation bridge, stree
- **Status:** `showcase`
- **Use when:** BEAR.Resourceの実行ツリーをSemantic Logger観察ログとして記録し、event extractionの入力にしたい。
- **着手前チェック（Before）:**
  - [ ] `ResourceObservationModule` で `InvokerInterface` をdecorateし、`LoggerInterface` はdecorateしないと理解したか。
  - [ ] `BodyStoreInterface` でrendered bodyを外部化し、`body_ref` で参照すると決めたか。
  - [ ] 開発時は `DevLogModule` でbodyファイルを自動クリア＋全method記録すると理解したか。
- **Source:**
  - `vendor/bear/event-sourcing/src/Resource/ResourceObservationModule.php`
  - `vendor/bear/event-sourcing/src/Resource/BodyStoreInterface.php`
  - `vendor/bear/event-sourcing/src/Resource/FileBodyStore.php`
  - `vendor/bear/event-sourcing/src/Resource/DevLogModule.php`
- **Tests:**
  - `tests/Smoke/ResourceObservationTest.php`（新規）
- **Key points:** `InvokerInterface` decorate で観察ログ生成。`BodyStoreInterface` でbody外部化。`DevLogModule` は開発用（全method記録＋自動クリア）。
- **Do not:** `LoggerInterface` をdecorateしない（`InvokerInterface` が正しいdecorate対象）。
- **マスター確認（After）:**
  - [ ] Resource実行後にSemantic Loggerログが生成され、Event抽出可能になることを green。

---

## C. BEAR.Defer — new patterns (2)

Requires `composer require bear/defer`. Example resources and tests
will be created in BEAR.Kata to demonstrate each pattern.

---

### C1 `defer-resource-request`

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
  - `vendor/bear/defer/src/Attribute/Defer.php`
  - `vendor/bear/defer/src/DeferInterceptor.php`
  - `vendor/bear/defer/src/Module/DeferModule.php`
  - `vendor/bear/defer/src/SyncDefer.php`
- **Tests:**
  - `tests/Resource/App/DeferTest.php`（新規）
- **Key points:** `#[Defer]` は `#[Link]` relを参照し、bodyからURI templateを展開。`DeferInterceptor` はAfter interceptor（proceed後にenqueue）。実行戦略はbindingで切り替え。
- **Do not:** Resource内でdefer callを手書きしない（`#[Defer]` で宣言的）。follow-up URIをhardcodeしない（`#[Link]` 経由）。
- **マスター確認（After）:**
  - [ ] 202 が即時返却され、follow-upが転送後に実行されることを green。

---

### C2 `defer-conditional`

**`DeferInterface::add()`で条件付きdeferを手動制御する**

- **ID:** `defer-conditional`
- **Aliases:** conditional defer, DeferInterface, manual defer, add(), flush(), DeferTransfer, ConnectionCloserInterface
- **Status:** `showcase`
- **Use when:** follow-up処理が条件付きの場合、`#[Defer]` を迂回して `DeferInterface::add()` で手動制御したい。
- **着手前チェック（Before）:**
  - [ ] `DeferInterface` と `ResourceInterface` をinjectし、`$defer->add($request)` で手動enqueueすると決めたか。
  - [ ] `DeferTransfer` がbase transfer後にconnectionをreleaseし、その後に `flush()` が走ることを理解したか。
- **Source:**
  - `vendor/bear/defer/src/DeferInterface.php`
  - `vendor/bear/defer/src/DeferTransfer.php`
  - `vendor/bear/defer/src/ConnectionCloserInterface.php`
  - `vendor/bear/defer/src/SapiConnectionCloser.php`
- **Tests:**
  - `tests/Resource/App/DeferConditionalTest.php`（新規）
- **Key points:** `DeferInterface::add(callable $request)` で手動enqueue。`DeferTransfer` は transfer → connection release → flush の順。`ConnectionCloserInterface` でSAPI別の接続解放。
- **Do not:** `DeferInterface` のsingleton queueをflushせずに放置しない（`flush()` はrequest boundaryで必須）。
- **マスター確認（After）:**
  - [ ] 条件付きでfollow-upがenqueueされ、転送後に実行されることを green。

---

## Summary

| Group | Source | Count | New code needed? |
|---|---|---|---|
| A | BEAR.Kata already-implemented | 11 | No — source-index.md entry only |
| B | BEAR.EventSourcing | 4 | Yes — composer dep + example + test |
| C | BEAR.Defer | 2 | Yes — composer dep + example + test |
| **Total** | | **17** | |

### Implementation phases

1. **Phase 1** — A group (11): add Kata entries to `docs/source-index.md`.
   No new code; verify all Source/Tests paths exist.

2. **Phase 2** — `composer require bear/defer bear/event-sourcing`.

3. **Phase 3** — B + C group (6): create example resources, modules,
   and tests in BEAR.Kata; add Kata entries to `docs/source-index.md`.

4. **Phase 4** — Quality gate: `composer tests` green (cs + sa + phpmd + phpunit).
