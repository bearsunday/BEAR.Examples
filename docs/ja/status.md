# プロジェクトの現状

[English](../status.md)

2026-06-01 時点の `1.x` ラインの短い現状整理です。ここでは
「何を学んだか」「何が実装済みか」「何がまだないか」を先に見える形にします。
詳細な in/out の正は `docs/scope.md` です。

## 学んだこと

1. **意味論から始めると drift が減る。** ALPS から fake data、JSON Schema、
   resource、SQL、生成 docs へ進むことで、プロジェクト全体が同じ語彙で
   監査できます。
2. **Fake は都合のよい test double ではなく framework semantics を写す必要がある。**
   `DbQueryInterceptor` は write でも `exec()` ではなく `getRow` / `getRowList`
   を通るため、`FakeSqlQuery` もその挙動に合わせる必要がありました。
3. **App resource と Page resource は責務が違う。** App は HAL+JSON と明示的な
   lifecycle filter を提供し、Page は reader/admin policy、form validation、
   redirect、session auth、ownership、CSRF を担います。
4. **Cache の教材は面を小さく分ける方が読める。** `#[Cacheable]`、`#[Embed]`
   dependency merge、body-derived な `fromAssoc()` dependency を別 resource に
   分けることで、QueryRepository の規則がテスト可能な形で見えます。
5. **延期には診断が要る。** 初期に "not built" としていた項目のいくつかは、
   vendor の挙動を読んで再現し、テストで固定したことで実装済みに変わりました。
   下の未完了項目は、単に未調査なのではなく意図的に残しているものです。

## 成し遂げたこと

- **Semantic/data pipeline:** ALPS profile、entity ごとの決定論的 fake data
  50 件、観測から作る JSON Schema、生成 API docs、LLM 向け docs。
- **HAL App API:** Article CRUD、Article publish state transition、Article
  collection filtering/paging、Author、Category、Tag、Media、Auth、cache
  showcase、HAL link/embed、JSON Schema request/response validation。
- **HTML Page surface:** public Qiq pages と、Article admin の create/edit、
  publish confirm、delete confirm、list、login、callback、logout、PRG redirect。
- **Auth と browser safety:** Google OAuth provider、fake auth provider、
  session-backed current user、admin author ownership、`AdminGuard`、
  admin form post の `#[SameOrigin]` と synchronizer-token CSRF。
- **Persistence:** Doctrine migration、seed、MySQL/SQLite setup、Ray.MediaQuery
  SQL、natural-key post-insert lookup、Fake/real の response shape parity。
- **Reference patterns:** Read/Write query split、Input DTO と scalar params の
  対比、native array DTO inputs、MediaQuery pager、result object、DML metadata、
  async embed entrypoint、streaming variation、固定 3 つの Article GET variation。
- **Test safety net:** Resource、Page、Hypermedia、Smoke、Entity、Interceptor/CSRF、
  cache、MySQL integration tests。MySQL がない環境では integration は skip します。

## まだできていないこと

credential、CI runtime、upstream behavior、あるいは production slice の大きさが
理由で残している項目です。

| Item | Current state | Next step |
|---|---|---|
| query-string 別 cache invalidation | `#[CacheableResponse]` / `#[Purge]` は一部 list read/write に配線済み。ただし purge は canonical URI 向けで、query-string variant ごとではない | variant key か広めの purge strategy を追加 |
| entity-level cache rollout | deleted resource と App-template rendering の挙動が cache path を壊しうるため entity resource は未適用 | upstream behavior が明確になってから再検討 |
| Async Docker CI smoke | runtime container はあるが、GitHub Actions で `composer parallel:up && composer parallel:demo` は未実行 | image build time と cache の見通しが立ったら workflow 追加 |
| Real Google OAuth integration test | production code は `league/oauth2-google` を使うが credential 付き integration test はない | OAuth env vars がある時だけ走る test を追加 |
| phpstan baseline | upstream type mismatch 2 件を抑制中 | upstream signature が緩和されたら baseline を落とす |
| production deployment slice | `ProdModule`、opcache/preload guide、Redis cache adapter、author ownership を超える role model はない | deployment example に育てる時だけ追加 |
| PSR-7 injection example | manual topic に対応する実行例がまだない | header/cookie/client-IP など、別パターンを教える小 resource を追加 |

## あえて作っていないもの

これらは backlog ではありません。

- full production CMS admin。現在の admin は server-rendered Article slice です。
- JavaScript-enhanced editing flow。主題は server-side Resource/Page pattern です。
- `authors` / `media` collection resource。既存 collection で list pattern は十分示しています。
- 広い CLI 生成面。`article-show` / `article-list` が `#[Cli]` を示し、DTO-backed write は scalar `#[Option]` と相性がよくありません。
- 追加の Article variation。3 つの比較 resource が固定の教材セットです。
- `app://self/` App entry point。public HTML entry は `Page/Index` で、HAL discoverability は具体的な top-level resource から示しています。
