# BEAR.Sunday manual coverage audit - 2026-06-04

## Purpose

Re-check MyVendor.Cms against the current BEAR.Sunday official manual
(`https://bearsunday.github.io/llms-full.txt`) after PR #53. The question is
not "does this repository use every BEAR.Sunday feature?", but "does it work as
a useful reference implementation and feature catalog?"

## Baseline

Current local baseline is `origin/1.x` after PR #53 (`Update project status
documentation`). The refreshed docs already make several things clear:

- Admin auth/authz and CSRF are implemented, not backlog.
- `article-publish` is implemented as a state-transition resource.
- Cache examples are deliberately split into small teaching surfaces.
- The main known manual-linked gaps are production slice, Redis cache adapter
  binding, `#[InputFile]`, and a few optional framework features. PSR-7
  injection is documented as missing, but it is not a good CMS teaching target
  unless a real use appears.

## Implementation update

The first implementation pass after this audit added the highest-value slices:

- `app://self/media-upload` demonstrates `#[InputFile]` with
  `FileUpload|ErrorFileUpload`, image validation, runtime storage, Media
  metadata persistence, and no-DB tests.
- Auth now supports Google by default and Auth0/OIDC with
  `CMS_AUTH_PROVIDER=auth0`, normalized `provider` + `subject`, and an
  `auth_identities` mapping table.
- The cache showcase now includes an explicit `#[DonutCache]` scalar HAL
  preview. HAL embed dependency teaching remains in the `#[Cacheable]` /
  `#[Embed]` examples because donut-hole placeholders are string-renderer
  oriented.
- `src/Module/ProdModule.php` overlays BEAR.Package's production module and
  optionally installs Redis QueryRepository storage with `CMS_REDIS_DSN`.
- PSR-7/request-context remains by design/deprioritized; application import is
  implemented as a companion example, while Application-as-a-Service and
  other-language connection remain companion-example candidates rather than CMS
  mainline features.
- `linkCrawl` / DataLoader is now implemented as a companion graph under
  `src/Resource/App/Crawl/*`: author → articles → tags, with
  `ArticleTagsDataLoader` proving one batched tag query.
- Production/security operations are now documented in `docs/production.md`
  and `docs/ja/production.md`: compile artifacts, optional Redis,
  SAST/taint commands, deployment boundary, and opt-in DAST/AI Auditor notes.
- Practical Google auth is now documented in `docs/auth-google.md` and
  `docs/ja/auth-google.md`, with an env-gated authorization URL smoke test.

## Strong coverage

These areas are already good reference material.

| Manual area | Current coverage |
|---|---|
| ROA / ResourceObject / method mapping | App resources and Page resources cover GET/POST/PUT/DELETE and state-transition POST. |
| DI / modules / context composition | `AppModule`, `FakeModule`, `TestModule`, `HtmlModule`, and fake/test/real contexts are strong examples. |
| AOP / attributes | `#[DbQuery]`, `#[Link]`, `#[Embed]`, `#[JsonSchema]`, `#[Input]`, `#[Cli]`, `#[Cacheable]`, `#[CacheableResponse]`, and `#[Purge]` are used in executable code. |
| MediaQuery / SQL interface | Read/write split, SQL file naming, pager, result object, affected rows sample, and FakeSqlQuery are all present. |
| HAL + ALPS | Profile, HAL links/embeds, generated docs, and hypermedia workflow tests are strong. |
| JSON Schema validation | Request/response validation plus DTO handling are implemented and tested. |
| Qiq HTML | Public pages and server-rendered Article admin are present. |
| QueryRepository cache | Leaf cache, embed dependency merge, body-derived `fromAssoc()`, list `#[CacheableResponse]`, and purge logging are present. |
| Resource crawl / DataLoader | `ResourceInterface::crawl()` is demonstrated by the author → articles → tags companion graph, with a focused test proving DataLoader batching. |
| Parallel / stream | `bin/async.php`, ext-parallel container, and `MediaStream` variation demonstrate the concepts. |
| CLI | `article-show` and `article-list` demonstrate `#[Cli]` / `#[Option]` without broad repetitive generation. |
| Security basics | `bear/security` dev dependency, `composer security`, Psalm taint stubs, `runTaintAnalysis=true`, and a security workflow exist. |

## Gaps worth adding or recently added

These would improve the feature catalog without turning the repository into a
full production product.

### 1. `#[InputFile]` media upload slice

**Why:** The official Resource Parameters chapter now includes typed file upload
via `#[InputFile]`. This CMS has a `Media` entity, so the domain fit is natural.

**Suggested shape:**

- Keep canonical `Media::onPost` scalar metadata if it remains useful.
- Add either `app://self/media-upload` or a non-canonical variation under
  `src/Resource/App/Variations/MediaUpload.php`.
- Use `Koriym\FileUpload\FileUpload|ErrorFileUpload|null` and `#[InputFile]`.
- Store into a deterministic test directory or avoid permanent storage in the
  first slice by returning validated metadata only.
- Add tests with `FileUpload::fromFile()` and an `ErrorFileUpload` case.

**Acceptance criteria:**

- No real browser required.
- File size/type errors are explicit.
- The example does not expand into a full asset-management UI.

### 2. Production slice: `ProdModule`, compile/preload, Redis

**Why:** Production is a major manual chapter and currently the largest
reference gap. The repository already has `composer compile`, cache examples,
and deployment notes, so this can be a small production-shaped slice.

**Suggested shape:**

- Add `src/Module/ProdModule.php` that installs BEAR.Package's prod module and
  clearly binds only project-specific production concerns.
- Add optional Redis storage binding behind env configuration, or a separate
  `RedisCacheModule` so default local runs stay hermetic.
- Document `composer compile`, `autoload.php`, `preload.php`, `.compile.php`,
  and `module.dot` in a short production guide.
- Add a compile smoke test/script if possible without requiring Redis.

**Acceptance criteria:**

- Default `composer test` remains no-DB and no-Redis.
- Production docs say which parts are examples and which require real infra.
- Redis is optional and skipped when env is absent.

### 3. Cache invalidation completion

**Why:** Cache is one of BEAR.Sunday's strongest differentiators. This CMS
already demonstrates several cache surfaces, but `docs/status.md` still calls
out query-string variant invalidation and entity-level cache rollout as gaps.

**Suggested shape:**

- Keep the current `#[Cacheable]` showcase small.
- Add a focused query-string variant invalidation example only when the purge
  strategy is clear.
- Revisit entity-level caching after the upstream delete/rendering behavior is
  stable enough to avoid teaching a workaround.

**Decision update (2026-06-04):** the custom article-list variant invalidator
was removed after reviewing whether users need that reference. Query-string
variant purge is valid application policy, but without a real CMS workflow it
does not teach a reusable BEAR.Sunday primitive.

**Acceptance criteria:**

- Tests prove the canonical collection URI purge and the dependency examples.
- The example does not blur the current cache showcase's three clean shapes.

### 4. Authentication provider and authorization patterns

**Why:** The CMS already has Google OAuth, Auth0/OIDC, `AuthInterface`, fake
auth, session-backed admin login, author ownership, CSRF, and `AdminGuard`.
The next useful step is not "add many social buttons", but make one path
copy-pasteable enough for readers to run.

**Suggested shape:**

- Keep Google as the canonical practical path because it is the default and
  lower-friction for a reference CMS.
- Keep Auth0/OIDC as the secondary provider-swap example for generic
  tenant-backed identity.
- Keep the `auth_identities` mapping concept: `(provider, subject) ->
  authorId`, instead of relying on email as the permanent account key.
- Consider a separate API bearer/JWT verification path only if the HAL API is
  going to expose protected write resources outside the Page admin.
- Extend authorization from author ownership to a small, explicit role policy
  only if the admin grows beyond "author manages own articles".

**Acceptance criteria:**

- Default tests still use `FakeAuthProvider` and remain credential-free.
- Provider-specific configuration lives in modules/providers, not resources.
- Tests pin replay/state handling, provider subject mapping, and authorization
  failure status codes.
- Google and Auth0 examples normalize into the same `AuthenticatedUser` shape.
- The practical docs make Google OAuth setup, callback URL, `.env`, author
  mapping, admin guard, and logout copy-pasteable.

**Decision update (2026-06-04):** implemented as `docs/auth-google.md`,
`docs/ja/auth-google.md`, and `GoogleAuthProviderSmokeTest`. Real token
exchange remains intentionally out of the default gate.

### 5. Security workflow completion

**Why:** The project already has partial security coverage, but the manual's
security chapter includes DAST and AI Auditor as well as SAST/taint.

**Suggested shape:**

- Update docs/manual map to mention existing `composer security`,
  `psalm.xml`, and `.github/workflows/security.yml`.
- Add an explicit `composer security:taint` alias if useful.
- Consider adding DAST as a manual or env-gated workflow, not default CI.
- Keep AI Auditor as a documented optional review command unless API/key
  assumptions are acceptable.

**Acceptance criteria:**

- No secret required for default checks.
- Manual false-positive handling (`@security-ignore`) is documented.
- CI signal stays actionable.

### 6. Crawl / DataLoader teaching graph

**Why:** The official Resource Link chapter now includes `linkCrawl()` and beta
DataLoader. The CMS domain has natural article-author-category-tag graphs.

**Suggested shape:**

- Add a small, explicitly educational crawl graph outside the fixed Article
  variation set.
- Use links that demonstrate a tree such as author -> articles -> tags.
- Add DataLoader only if the current BEAR.Resource 1.x-dev API is stable enough
  for this repository's reference role.

**Acceptance criteria:**

- Demonstrates a real N+1 reduction, not just syntax.
- Tests assert batched query shape or call count.
- Does not add a fourth Article GET variation.

**Decision update (2026-06-04):** implemented as `src/Resource/App/Crawl/*`
plus `ArticleTagsDataLoader`. `CrawlDataLoaderTest` asserts one
`tag_list_by_articles` query and zero per-article `tag_list_by_article` queries.

## Gaps to classify as partial or by-design

These should be documented, but not necessarily implemented.

| Manual area | Recommendation |
|---|---|
| Ray.WebFormModule / `#[FormValidation]` | Keep partial. This CMS intentionally uses Page resources, App JSON Schema validation, PRG, and explicit CSRF. Add WebForm only if there is a separate form-focused teaching goal. |
| Ray.ValidateModule `#[Valid]` | Keep missing/optional. JSON Schema + DTO validation is the canonical path here; add `#[Valid]` only if a cross-cutting AOP validation example is needed. |
| BEAR.Accept / `#[Produces]` | Reclassify as partial. The project has separate HAL and HTML contexts, but no runtime Accept negotiation or resource-level `#[Produces]`. A tiny CSV or alternate media resource could be added later. |
| PSR-7 / request context | Deprioritize or mark by design. A diagnostics resource is easy to write, but it does not serve a CMS use case and is likely to remain unused. |
| Siren | By design. HAL+ALPS is the semantic contract for this reference. |
| Twig | By design. Qiq is the chosen renderer. |
| JavaScript UI / SSR | By design. The reference focuses on server-side Resource/Page patterns. |
| RoadRunner / FrankenPHP | By design for now. Swoole scaffolding exists, but full persistent-worker examples would be a production/runtime project. |
| Broad CLI / Homebrew distribution | Partial/by design. `article-show` and `article-list` teach `#[Cli]`; formula/tap distribution can remain docs-only unless the project becomes a CLI distribution example. |
| Import / Application as a Service | Import is demonstrated as an isolated companion example; Application-as-a-Service and other-language connection remain by design outside the CMS mainline. |

## Recommended phases

### Phase A - documentation alignment

Update `docs/manual-map.md`, `docs/status.md`, and `docs/scope.md` so the
manual coverage categories above are visible:

- Change content negotiation from "Done" to "Partial" unless `#[Produces]` is
  added.
- Add rows or notes for `#[InputFile]`, `#[Valid]`, crawl/DataLoader, Siren,
  security tooling, and high-performance servers.
- Keep by-design choices explicit so future agents do not try to fill every
  optional manual feature.

### Phase B - low-risk executable example

Add `#[InputFile]` media upload first. It is small, testable, and
domain-relevant.

### Phase C - production/security operating reference

Expand the `ProdModule`/compile/preload/Redis/security guide into an
operational reference. Keep Redis, DAST, and AI Auditor opt-in.

**Decision update (2026-06-04):** implemented as `docs/production.md`,
`docs/ja/production.md`, `composer security:sast`, and
`composer security:taint`.

### Phase D - practical Google auth reference

Document one Laravel-level Google OAuth setup path: client setup, consent
screen, callback URL, `.env`, author identity mapping, admin guard, logout, and
env-gated smoke checks. Keep Auth0/OIDC secondary.

**Decision update (2026-06-04):** implemented. Auth0/OIDC remains documented as
the secondary provider adapter.

### Phase E - optional media negotiation

Consider `#[Produces]` or BEAR.Accept only if the project needs an explicit
content-negotiation example beyond the existing HAL/Page context split.

## Next decision

The next implementation target, if continuing the catalog, is optional companion
evaluation for `#[Produces]`/BEAR.Accept, Halo dev context, and async/Swoole
manual-dispatch smoke. `#[InputFile]`, application import, crawl/DataLoader,
production/security operations, and practical Google auth are now implemented.
