# Project status

[日本語](ja/status.md)

Current as of 2026-06-01 on the `1.x` line. This page is the short
status map: what the project taught us, what is implemented, and what
is still intentionally absent or deferred. `docs/scope.md` remains the
detailed source of truth.

## What we learned

1. **Semantic-first construction reduces drift.** Starting from ALPS,
   then deriving fake data, JSON Schema, resources, SQL, and generated
   docs gives the project one vocabulary to audit.
2. **The fake must model framework semantics, not convenient test
   shortcuts.** `DbQueryInterceptor` routes writes through
   `getRow`/`getRowList`, not `exec()`, so `FakeSqlQuery` has to preserve
   that behavior for the no-DB stack to be meaningful.
3. **App and Page resources have different responsibilities.** App
   resources expose HAL+JSON and explicit lifecycle filters; Page
   resources apply reader/admin policy, form validation, redirects,
   session auth, ownership checks, and CSRF protection.
4. **Cache examples need narrow surfaces.** The cache showcase keeps
   `#[Cacheable]`, `#[Embed]` dependency merging, and body-derived
   `fromAssoc()` dependencies separate so each QueryRepository rule is
   visible and testable.
5. **Deferral needs diagnosis.** Several early "not built" items later
   became implemented once vendor behavior was read, reproduced, and
   pinned with tests. The remaining gaps below are deliberately scoped,
   not merely untried.

## What is done

- **Semantic and data pipeline:** ALPS profile, deterministic 50-row fake
  data per entity, observed JSON Schema, generated API docs, and LLM docs.
- **HAL App API:** Article CRUD, Article publish state transition,
  Article collection filtering/paging, Author, Category, Tag, Media,
  Auth, cache showcase resources, HAL links/embeds, and JSON Schema
  request/response validation.
- **HTML Page surface:** public Qiq pages plus Article admin create/edit,
  publish confirmation, delete confirmation, article listing, login,
  callback, logout, and PRG redirects.
- **Auth and browser safety:** Google OAuth provider, fake auth provider,
  session-backed current user, admin author ownership, `AdminGuard`,
  `#[SameOrigin]`, and synchronizer-token CSRF checks on unsafe admin
  form posts.
- **Persistence:** Doctrine migrations, seed script, MySQL and SQLite
  setup paths, Ray.MediaQuery SQL files, natural-key post-insert lookup,
  and Fake/real response-shape parity.
- **Reference patterns:** Read/Write query split, Input DTOs contrasted
  with scalar params, native array DTO inputs, MediaQuery pager,
  result objects, DML metadata, async embed entrypoint, streaming
  variation, and three fixed Article GET comparison variations.
- **Test safety net:** Resource, Page, Hypermedia, Smoke, Entity,
  Interceptor/CSRF, cache, and MySQL integration tests, with integration
  tests skipping when MySQL is unavailable.

## What is not done

These are deferred because they need upstream movement, credentials, CI
runtime work, or a larger production slice.

| Item | Current state | Next step |
|---|---|---|
| Per-query-string cache invalidation | `#[CacheableResponse]` / `#[Purge]` are wired for selected list reads and writes, but purge targets the canonical URI, not each query-string variant | Add explicit variant keys or a broader purge strategy |
| Entity-level cache rollout | Entity resources remain uncached because deleted-resource and App-template rendering behavior can mutate or break the cache path | Revisit after the upstream behavior is clarified |
| Async Docker CI smoke | Runtime containers exist, but GitHub Actions does not yet run `composer parallel:up && composer parallel:demo` | Add a focused workflow once image build time and caching are acceptable |
| Real Google OAuth integration test | Production code uses `league/oauth2-google`; no credential-backed integration test exists | Add an env-gated test that skips unless Google OAuth env vars are present |
| phpstan baseline | Two upstream type mismatches remain suppressed | Drop the baseline entries after upstream signatures relax |
| Production deployment slice | No `ProdModule`, opcache/preload guide, Redis cache adapter binding, or production role model beyond author ownership | Add only when this reference grows into a deployment example |
| PSR-7 injection example | Manual topic has no executable example here | Add a small header/cookie/client-IP resource if it teaches a distinct pattern |

## By design

These are not backlog.

- No full production CMS admin: the admin is a server-rendered Article
  slice, not a product UI.
- No JavaScript-enhanced editing flow: server-side Resource/Page patterns
  are the subject.
- No `authors` or `media` collection resource: existing collections already
  show the list pattern.
- No broad generated CLI surface: `article-show` and `article-list` show
  the `#[Cli]` pattern; DTO-backed writes are not a good fit for scalar
  `#[Option]` generation.
- No additional Article variation: the three comparison resources are the
  fixed teaching set.
- No `app://self/` App entry point: `Page/Index` is the public HTML entry,
  and HAL discoverability is demonstrated from concrete top-level resources.
