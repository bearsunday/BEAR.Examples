# Handoff for the next session

This file is what a fresh AI (or human) should read first when picking
the project up. It is intentionally short — pointers, not narrative.

---

## What BEAR.Cms is

A reference implementation of a CMS built on **BEAR.Sunday + ALPS +
semantic-ex + Ray.MediaQuery + BDR pattern**. The primary surface is a
HAL+JSON App resource API, with a read-only Qiq/Page HTML projection for
browser inspection. Authenticated/admin write UI is not built yet.
Designed so that an AI (or human) reading the codebase can learn the
canonical naming, structure, and flow.

Five entities: Article, Category, Tag, Author, Media. Read + Write
across each, plus an Auth resource (Google OAuth via league/oauth2-google,
swappable to FakeAuthProvider in tests).

---

## How to verify it works in 60 seconds

```bash
composer install
composer fake               # regenerate var/fake/*.json (deterministic, mt_srand(42))
composer schema             # regenerate var/json_schema/*.json from the fake
composer test               # default PHPUnit suites; integration auto-skips
                            # when MySQL is unreachable
composer demo               # 7-section walkthrough that exercises everything
                            # (auto-detects malt → docker → sqlite for the real-DB section)
composer serve:api          # HAL JSON API at http://127.0.0.1:8080
composer serve:html         # Qiq/Page HTML at http://127.0.0.1:8081
```

Real DB setup: `composer malt:up` (macOS), `composer docker:up`
(cross-platform), or `composer sqlite:up` (CI / no docker).

---

## What is canonical (do not change without intent)

These are the design choices the project demonstrates. Touching them
defeats the reference value.

- `src/Resource/App/*` — every URI is a class. `onGet` / `onPost` /
  `onPut` / `onDelete`. Body is set on `$this->body`; HAL renderer
  emits `_links` from `#[Link]`, `_embedded` from `#[Embed] + addQuery`.
- `src/Entity/*` — `final readonly class` with public properties.
  Predicates (`isPublished`, `belongsToCategory`) live here.
  `Article` carries an injected `MarkdownRendererInterface` to demo
  `FetchInjectionFactory`.
- `src/Query/*` — both Read (`*QueryInterface`) and Write
  (`*CommandInterface`) live in this single directory. Each method has
  `#[DbQuery('<entity>_<verb>')]`. SQL files in
  `var/db/sql/<entity>_<verb>.sql`. Read SQL columns are ordered to
  match the entity constructor (PDO::FETCH_FUNC).
- New-id-after-INSERT pattern: Resource calls
  `$this->article->bySlug($slug)` after `$this->articleCmd->add(...)`.
  Avoids driver-specific `lastInsertId`. Slug/email/filename are the
  natural unique keys. See `conventions.md` §3 for the
  `item` / `by<NaturalKey>` / `list` query method naming and the
  `$<entity>` / `$<entity>Cmd` Resource property naming.
- ALPS profile (`var/alps/profile.json`) is the source of truth for
  Choreography names. HAL `_links` rels match those names
  (`goArticleList`, `doCreateArticle`, etc.).
- JSON Schemas in `var/json_schema/` validate response bodies via
  `#[JsonSchema('<entity>.json')]`. Input schemas in
  `var/json_validate/` validate request params via
  `#[JsonSchema(schema: 'write_response.json', params: '<entity>_<verb>.json')]`.
  Validation runs *after* DTO hydration in BEAR.Resource 1.31.1, so
  typed-array DTO fields (e.g. `public array $tagIds`) must defend
  themselves against malformed shapes — declare them `mixed`, gate
  with `is_array`, and throw `ParameterException` (→ 400). See
  `conventions.md` §4 "Pitfall: typed-array DTO fields and the
  validation order" and `src/Input/ArticleCreateInput.php` for the
  canonical pattern.
- App contexts:
  - `hal-api-app` — production HTTP
  - `cli-hal-api-app` — `bin/app.php`, `composer app`
  - `fake-hal-api-app` — dev runtime against `tests/Fake/FakeSqlQuery.php`
  - `test-hal-api-app` — PHPUnit (unit suites)
- HTML contexts:
  - `html-hal-app` — production Page/Qiq HTTP
  - `cli-html-hal-app` — `bin/page.php`, `composer page`
  - `html-test-hal-api-app` — PHPUnit Page tests against FakeSqlQuery
- `composer demo` is the entry point for verifying any change end-to-end.

---

## Open upstream issues (might land between sessions)

Both filed during the build:

| Issue | Repository | Topic |
|-------|-----------|-------|
| [#76](https://github.com/bearsunday/BEAR.ApiDoc/issues/76) | BEAR.ApiDoc | Pull data from semantic-ex artifacts (ALPS, JSON Schema, fake data) into generated docs |
| [#355](https://github.com/bearsunday/BEAR.Resource/issues/355) | BEAR.Resource | JsonSchemaInterceptor should skip body validation on cache hit (CacheableResponse interaction) |

If either lands, BEAR.Cms can adopt the fix:
- ApiDoc#76 → richer `composer doc` output (no project-side change needed beyond bumping version)
- Resource#355 → reattach `#[CacheableResponse]` across all read resources

---

## Known gaps (deferred deliberately)

| Item | Why deferred | Recovery |
|------|------|---------|
| **Step 5.5: Async `#[Embed]` parallelisation** | `bear/async ^0.1` requires `bear/resource ^1.31`; current is `^1.17`. Upgrade is a multi-package breaking change | Try `composer require bear/async -W` in a branch, fix any API drift, run full test suite |
| **Step 6: `#[CacheableResponse]` on all reads** | Blocked on BEAR.Resource#355 (cache hit + JsonSchema interaction). Currently zero resources have the attribute | When #355 lands, restore class-level `#[CacheableResponse]` on read resources + `#[RefreshCache]` on writes |
| **phpstan baseline (2 entries)** | One vendor-interface return-type mismatch in `tests/Fake/FakeSqlQuery.php` (`getRowList` returns `list<object>` but `SqlQueryInterface` declares `array<array<mixed>>`); one OAuth provider arg-type widening. Both intentionally suppressed — see comment in `phpstan-baseline.neon`. | Wait for upstream `SqlQueryInterface` to relax its return type; then drop the entry |
| **Write-side CLI** | Only `article-show` / `article-list` are generated. `article-add` / `article-update` / `article-delete` would round out the demo | Add `#[Cli]` to onPost/onPut/onDelete; `composer cli` regenerates |
| **Real Google OAuth verification** | Code uses `league/oauth2-google` correctly but no integration test against real Google (needs creds + callback URL) | Add `tests/Integration/AuthGoogleTest.php` that skips unless `GOOGLE_CLIENT_ID` is set |

---

## Failure modes from the build (do not repeat)

Distilled from `docs/journal/review-skill.md`, which is itself the
artefact of this session's biggest failure.

1. **"Tests pass" ≠ "it works."** Unit tests run against
   `FakeSqlQuery`. Real-DB path is verified only by `tests/Integration/`
   and `composer demo`. Always run both before declaring done.
2. **Deferral requires diagnosis.** Do not defer something after a
   single error. Read the relevant vendor source, run one experiment,
   *then* decide. (Step 6 was deferred too early on this point.)
3. **Cache log > debugger.** `BEAR\QueryRepository\RepositoryLogger` is
   bound singleton. Hold a reference before the request, echo
   `(string)$logger` after — you see `try-donut-view` / `put-donut` /
   `invalidate-etag` etc. Often answers "why is this caching weirdly?"
   before you reach for xtrace. Demonstrated on Step 6 diagnosis.
4. **Run `review-skill` BEFORE commit, not after pushback.** The skill
   exists in `docs/journal/review-skill.md`. Apply Step 6 (self-check)
   to your own writing before announcing it done.

---

## Things that would benefit from promoting outside this repo

The following journal docs are not BEAR.Cms specific and would help a
broader audience if extracted:

- `docs/journal/review-skill.md` — generic AI review failure-mode skill;
  candidate to PR into `bearsunday/BEAR.Skills`
- `docs/journal/skill-proposals.md` — 12 proposed skills for BEAR.Skills

Until they move, they live in this project's journal.

---

## Files an AI should read before writing code

Read order and per-question index live in `README.md` →
"Using this as a reference". The rulebook is `docs/conventions.md`;
new code lands there first. This handoff intentionally does not
duplicate the list — keeping a single source of truth means an
update to the canonical reading order propagates without touching
this file.

---

## Local state on this machine (will not transfer)

- malt MySQL is running on 127.0.0.1:3306 (database `bear_cms`, root,
  no password). `composer malt:up` recreates if you need it.
- A separate AI failure-narrative archive lives in
  `~/Documents/bear-cms-archive/` (5 files: `framework-critique.md`,
  `critique-self-review.md`, `critique-second-pass.md`,
  `session-review.md`, `verified.md`). Useful for studying review
  failure modes; not part of the public repo.
