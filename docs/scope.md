# Scope: what's in, what's not

A snapshot of what this reference codebase demonstrates and where the
known gaps are. The codebase is a **reference CMS** — its purpose is
to show canonical patterns (BEAR.Sunday + ALPS + Ray.MediaQuery + BDR),
not to be feature-complete. Some omissions are deliberate contrasts;
others are deferred until upstream is ready. Every claim is grounded
in the current `1.x` HEAD — if you find a discrepancy, that's a doc bug.

## In scope (implemented)

### Domain & data

| Item | Notes |
|------|-------|
| 5 entities | `Article`, `Author`, `Category`, `Tag`, `Media` — all `final readonly class` with public properties |
| Status enum | `ArticleStatus` (`draft` / `published`) |
| Fake data | 50 records / entity, deterministic (`mt_srand(42)`), referential integrity. Regenerable via `composer fake` |

### App resources (HAL+JSON)

| Resource | Verbs | Notes |
|----------|-------|-------|
| `app://self/article` | GET / POST / PUT / DELETE | Full CRUD; POST/PUT accept `tagIds` (tri-state `null` / `[]` / list) |
| `app://self/articles` | GET | Filter (`status`, `categoryId`, `tagId`, `authorId`) + page/perPage |
| `app://self/author` | GET / POST / PUT | No DELETE; no `authors` collection (asymmetric — see "By design") |
| `app://self/category` / `categories` | GET / POST / PUT / DELETE | |
| `app://self/tag` / `tags` | GET / POST / DELETE | |
| `app://self/media` | GET / POST / DELETE | No `media` collection (asymmetric — see "By design") |
| `app://self/auth` | GET / POST | OAuth flow: GET returns authorization URL, POST exchanges `{code, state}` |
| `app://self/cache/author` | GET / PUT | Cache showcase leaf — user-zero-code (`#[Cacheable]` only) |
| `app://self/cache/authorprofile` | GET | Cache showcase parent — `#[Embed]`-only automatic dependency (single-child, zero cache code; since `bear/query-repository` 1.16) |
| `app://self/cache/tag` | GET / PUT | Cache showcase leaf — user-zero-code (`#[Cacheable]` only) |
| `app://self/cache/articletags` | GET / PUT | Cache showcase parent — one-line `fromAssoc` for body-derived variable-length dependency set; PUT is the showcase's own write entry point (main `app://self/article` writes are intentionally out of scope) |

There is no `app://self/` entry point at the App layer; `Page/Index`
serves as the public HTML entry. (Discoverability via HAL `_links` is
demonstrated from each top-level resource.)

### Page resources (Qiq HTML)

| Surface | Resources | Verbs |
|---------|-----------|-------|
| Public read-only | `Index`, `Article`, `ArticleList`, `ArticleFeed`, `Author`, `AuthorList`, `Category`, `CategoryList`, `Tag`, `TagList` | GET only |
| Admin write | `Page/Admin/Article` (create / edit form) | GET, POST |
| Admin write | `Page/Admin/ArticleDelete` (confirm form) | GET, POST |
| Admin read | `Page/Admin/ArticleList` | GET only |

Admin pages wrap the App resources via `$this->resource->post/put/delete(...)`. PRG: success → 303 redirect with `?saved=created|updated|deleted`.

### Resource variations (reading material, not API)

`src/Resource/App/Variations/` — three alternative implementations of the
Article GET to compare entity vs array, declarative vs programmatic query, and
MediaQuery vs raw PDO. It also contains `MediaStream`, a separate transfer-mode
variation that demonstrates `BEAR.Streamer` without changing canonical
`Media::onGet()`. Run `composer demo:variations`.

### Hypermedia

| Feature | Where | Notes |
|---------|-------|-------|
| `_links` | `#[Link]` attributes (RFC 6570 templates) | Rels follow ALPS Choreography names (`goArticleList`, `goAuthor`, …) |
| `_embedded` | `#[Embed]` + `addQuery()` for parametric embeds; manual array build inside `onGet` for ID-after-fetch cases | |
| ALPS profile | `var/alps/profile.json` (single source of truth) | HTML rendering via `composer doc` (`asd`) |

### Validation

| Layer | Mechanism | Coverage |
|-------|-----------|----------|
| Response body | `#[JsonSchema(schema: '...')]` | All read resources (GET on `Article`, `Articles`, `Author`, `Category`, `Categories`, `Tag`, `Tags`, `Media`); `Auth::onPost` (separate `auth_response.json` for string subject id). `Auth::onGet` and DELETE methods return without body validation |
| Request params | `#[JsonSchema(params: '...')]` | POST and PUT on the resources above (DELETE takes only `int $id`, no params schema) |
| Input DTO | `#[Input]` + `Ray\InputQuery` | `ArticleCreateInput`, `ArticleUpdateInput`, `AuthExchangeInput`. Author / Category / Tag / Media remain scalar by intentional contrast — see "By design" |
| Typed-array DTO field defence | `mixed` + `is_array` gate → `ParameterException` (→ 400) | See `conventions.md` §4 "Input DTO pitfalls" |

### Auth

| Item | Notes |
|------|-------|
| `AuthInterface` | Backend abstraction |
| `GoogleAuthProvider` | `league/oauth2-google` |
| `FakeAuthProvider` | Test/fake context |
| `AuthenticatedUser` | `final readonly` |
| `Auth` resource | Auth flow shape with response schema |
| `AuthSessionInterface` | Session-backed current-user, OAuth state, login, and logout boundary |
| `AdminUserInterface` | Protects `Page/Admin/*` resources and carries author ownership |
| Page/Admin unauthenticated access | HTML error handler converts `UnauthenticatedException` from typed admin injection into `303 Location: /admin/login` |
| OAuth configuration guard | Missing `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, or `GOOGLE_REDIRECT_URI` returns local `503` with `Google OAuth is not configured.` instead of redirecting to a broken Google URL |

Note: `Page/Admin/*` is now behind typed `AdminUserInterface` injection. The remaining auth follow-up is CSRF protection for admin form posts (see Deferred §).

### Persistence & migrations

| Item | Notes |
|------|-------|
| Doctrine Migrations | `var/db/migrations/` |
| Seed | `bin/seed.php` loads the fake into a real backend |
| SQL files | `var/db/sql/<entity>_<verb>.sql` — column order matches `__construct` (PDO::FETCH_FUNC) |
| Read SQL contract | Single `#[DbQuery]` interceptor routes by return type to `getRow` / `getRowList`; `exec()` is unused |
| Backends | MySQL (Malt or docker compose), SQLite (CI / no-docker fallback) |

### Testing

| Suite | Target | Backend |
|-------|--------|---------|
| `tests/Resource/App` | App API | `FakeSqlQuery` (no DB) |
| `tests/Resource/Page` | Page HTML | `FakeSqlQuery` via `html-test-hal-api-app` |
| `tests/Hypermedia` | HAL link/embed contract | Fake |
| `tests/Smoke` | Lifecycle smoke | Fake |
| `tests/Entity` | Entity invariants | n/a |
| `tests/Integration/<Entity>MySQLTest.php` | 4 entities (Article, Author, Category, Tag) | Real MySQL (auto-skips when unreachable) |
| `tests/params` | JSON-Schema params validation | n/a |

`composer test` runs the full suite. `composer demo` is a 7-section walkthrough that auto-detects malt → docker → sqlite for the real-DB section.

### CLI & tooling

| Command | Purpose |
|---------|---------|
| `composer fake` | Regenerate `var/fake/*.json` |
| `composer schema` | Regenerate `var/json_schema/*.json` |
| `composer semantic` | Both of the above |
| `composer cli` | `bear-cli-gen` — generates `bin/cli/*` from `#[Cli]`-annotated resources |
| `composer serve` / `serve:api` | HTML / API HTTP servers |
| `composer demo` | End-to-end walkthrough |
| `composer demo:cache` | QueryRepository cache showcase (hermetic, in-memory ArrayAdapter) |
| `composer doc` | apidoc + ALPS HTML |
| `composer compile` | bear.compile production graph |

Generated read-side commands exist for `article-show` and `article-list` (under `bin/cli/`). Equivalents for the other entities and all write-side commands are not generated yet — see Deferred §.

### Reference patterns shown

Patterns the codebase deliberately demonstrates (each appears in at least one place, not necessarily everywhere):

| Pattern | Where |
|---------|-------|
| `FetchInjectionFactory` (DI into hydrated entity) | `Page/Article` injects `MarkdownRendererInterface` |
| Input DTO via `#[Input]` + `Ray\InputQuery` | `Article` (POST/PUT), `Auth` (POST) — contrasted against scalar `onPost` on Author/Category/Tag/Media |
| Tri-state optional collection input | `tagIds` on `ArticleCreateInput` / `ArticleUpdateInput` |
| Ray.MediaQuery pager | `ArticleQueryInterface::list()` / `PagesInterface` |
| Ray.MediaQuery SELECT result class | `ArticleSelectionQueryInterface::list()` / `ArticleSelection` |
| Named Generator traversal | `ArticleSelection::published()` yields published `Article` rows without template-side status checks |
| CQRS query-side projection | `ArticleSelection::feed()` yields disposable `ArticleFeedItem` read models for `Page/ArticleFeed` |
| Ray.MediaQuery DML metadata result | `Samples\ArticleAffectedRowsCommandInterface` / `AffectedRows` |
| Natural-key `by<Key>` post-INSERT lookup | `Article::onPost` → `bySlug`; same idea for `byEmail` / `byFilename` |
| Manual `_embedded` build for ID-after-fetch | `Article::onGet` (`author`, `category`, `tagList`) |
| BEAR.Async opt-in embed parallelization | `bin/async.php` overlays `ParallelRuntimeModule`; Article's `author` / `category` / `tagList` embeds are the reference graph |
| Three Article GET implementation variations | `src/Resource/App/Variations/` (`composer demo:variations`) |
| Stream transfer response | `Variations\MediaStream` uses `BEAR.Streamer` and an open file handle body |
| QueryRepository cache — user-zero-code leaf | `Cache\Author`, `Cache\Tag` (`#[Cacheable]` only; reflection-pinned) |
| QueryRepository cache — `#[Embed]`-only parent (single-child, auto-merged) | `Cache\AuthorProfile`; reflection-pinned to zero manual cache code (since `bear/query-repository` 1.16.0) |
| QueryRepository cache — one-line `fromAssoc` parent (N-child, body-derived) | `Cache\ArticleTags`; reflection-pinned to exactly one `fromAssoc` call |
| PRG redirect on admin write | `Page/Admin/Article` and `Page/Admin/ArticleDelete` redirect 303 with `?saved=…` |

### Documentation surface

`README.md` → `docs/{en,ja}/reading-guide.md` → `docs/architecture.md` → `docs/conventions.md` → `docs/resources.md` → `docs/alps.md` → `docs/journal/*`. Conventions is the canonical rulebook for new code.

---

## Historical: resolved upstream blockers

These were once blockers that prevented the canonical pattern from being shown; they are listed here so the journal trail makes sense (they're already reflected in the In-scope tables above).

| # | Item | Closed by |
|---|------|-----------|
| R1 | DTO recognition by `JsonSchemaInterceptor` ([BEAR.Resource#356](https://github.com/bearsunday/BEAR.Resource/issues/356)) | BEAR.Resource 1.31.1 — `Article` / `Auth` re-attached `#[JsonSchema]` |
| R2 | OpenAPI generator skipped DTO methods ([BEAR.ApiDoc#81](https://github.com/bearsunday/BEAR.ApiDoc/issues/81)) | BEAR.ApiDoc 1.9.1 |
| R3 | `JsonSchema` body validation on cache hit ([BEAR.Resource#355](https://github.com/bearsunday/BEAR.Resource/issues/355)) | BEAR.Resource 1.31.1 — unblocked the cache showcase under `src/Resource/App/Cache/*` (`composer demo:cache`); main-resource rollout remains D1 |
| R4 | Typed-array DTO field × validation order pitfall ([decisions P8 #46](journal/decisions-to-consult.md)) | Defensive `mixed` + `is_array` guard documented in `conventions.md` §4 |
| R7 | Admin write/delete failure propagation (CodeRabbit feedback on PR #18) | Commit `0d7f98d` — `Page/Admin/Article` and `Page/Admin/ArticleDelete` propagate 4xx codes back instead of redirecting |

---

## By design (intentional omissions)

These aren't bugs or backlog — they're deliberate choices that keep the reference focused.

| Item | Why this way |
|------|--------------|
| Scalar `onPost` / `onPut` on Author, Category, Tag, Media | Kept scalar so a reader sees both styles side-by-side. `Article` and `Auth` show the `#[Input]` + DTO style; the others show plain scalar parameters. Migrating all four would erase the contrast |
| No `authors` or `media` list resource | The two collections that exist (`articles`, `categories`, `tags`) are enough to demonstrate the list pattern, filtering, and pagination. Adding more would be repetition |
| No `app://self/` entry point | `Page/Index` is the public HTML entry; HAL discoverability is shown via per-resource `_links` |
| JS-enhanced admin (HTMX or similar) | Out of demonstration scope; the patterns to demonstrate are server-side. An optional add-on would not change App-layer code |
| Applying `#[Cacheable]` to the main `Article` resource | `Article` composes three embeds (`author`, `category`, `tagList`) and `tagList` is itself a body-derived variable-length list. Mixing `#[Embed]`-driven composition and `fromAssoc()`-driven cross-resource invalidation on the same response is exercised by the `Cache\*` showcase as the canonical pattern; leaving the main `Article` untouched keeps the principal resource side-by-side comparable against the showcase rather than entangling the two demos |

## Deferred / not built

Drawn from `architecture.md` "What was intentionally not built", `journal/handoff.md` "Known gaps", and `journal/decisions-to-consult.md` P4. Recovery column tells you what unblocking each one looks like.

| # | Item | Why deferred | Recovery / next step |
|---|------|--------------|----------------------|
| D1 | `#[CacheableResponse]` on list reads + `#[Purge]` on writes (PR-C2) | **Partial — landed for non-embedded list reads.** `Articles` / `Categories` carry class-level `#[CacheableResponse]`; `Article` / `Category` writes carry `#[Purge(uri: 'app://self/{collection}')]`. Two intentional exclusions: (1) entity resources skip class-level caching because `DonutCommandInterceptor` re-runs `onGet` on deleted entities and mutates `204 → 404`; (2) `Tags` skips caching because it is embedded in `Article` via `#[Embed(rel: 'tagList')]` — when the html context materialises the embed, the donut pipeline calls `(string) $ro` and `CmsQiqRenderer` has no App-template, throws, and breaks the ETag chain. Note: `#[Purge(uri)]` invalidates the canonical URI only, not query-string variants (e.g. `?categoryId=3`) | Pipeline verified in `tests/Resource/App/CacheTest.php` (asserts `try-donut-view` / `put-donut` / `save-etag` / `purge-query-repository` in `RepositoryLogger`). Future follow-ups: per-query-string purge keys and entity-level caching once the delete-mutation upstream behavior is clarified |
| D2 | ~~Auth boundary for `Page/Admin/*`~~ | Implemented with typed `UserInterface` / `AdminUserInterface` providers, session-backed OAuth login, author-scoped admin ownership, unauthenticated redirect to `/admin/login`, and local OAuth-missing configuration error | Remaining follow-up: CSRF protection |
| D3 | Async Docker CI smoke | Runtime containers exist, but CI does not yet build ext-parallel and run `composer parallel:demo` | Add a focused GitHub Actions job once image build time and caching are acceptable |
| D4 | Write-side CLI + read CLI for the other entities | `bear-cli-gen` so far only generated `article-show` / `article-list`; no write commands yet | Add `#[Cli]` to onPost/onPut/onDelete and to the missing read methods; `composer cli` regenerates |
| D5 | Real Google OAuth integration test | Needs creds + callback URL | env-gated test that skips unless `GOOGLE_CLIENT_ID` is set |
| D6 | MySQL integration coverage for `Media` | 4 of 5 entities covered (`tests/Integration/`) | Add `MediaMySQLTest` mirroring the existing pattern |
| D7 | `Articles` collection `totalCount` | Implemented via MediaQuery Page `total` | Keep schema/docs in sync when list shape changes |
| D8 | `#[Pager]` / `PagesInterface` adoption decision | Adopted for Article collection reads; fake uses Pagerfanta `ArrayAdapter` | Extend the same pattern if other collections need paging |
| D9 | phpstan baseline (2 entries) | Upstream `SqlQueryInterface` return-type narrows; OAuth provider arg-type widening | Wait for upstream relaxation, then drop entries |
| D10 | Migration to `bearsunday/coding-standard` | Drafted as [coding-standard-roadmap/003](journal/coding-standard-roadmap/003-myvendor-cms-adopts-bearsunday-cs.md); blocked on the package's v0.1 + 001 (`@input-param` expansion) landing | After upstream lands, swap composer dependency and run the migration playbook in 003 |

---

## Sources

The information above is consolidated from these existing journal entries; this doc is the menu, those are the conversation behind each line item.

- [`docs/architecture.md`](architecture.md) "What was intentionally *not* built" — design-decision framing
- [`docs/journal/handoff.md`](journal/handoff.md) "Known gaps (deferred deliberately)" — operational view with recovery instructions
- [`docs/journal/decisions-to-consult.md`](journal/decisions-to-consult.md) P4 "黙ってスコープから落とした項目" / P8 "後追いで埋めたい穴" — original deferral rationale
- [`docs/journal/auth-boundary-plan.md`](journal/auth-boundary-plan.md) — auth boundary design (D2)
- [`docs/journal/coding-standard-roadmap/`](journal/coding-standard-roadmap/) — coding-standard package roadmap (D10)
