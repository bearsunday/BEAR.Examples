# Handoff for the next session

This file is what a fresh AI (or human) should read first when picking
the project up. It is intentionally short — pointers, not narrative.

---

## What BEAR.Cms is

A reference implementation of a CMS built on **BEAR.Sunday + ALPS +
semantic-ex + Ray.MediaQuery + BDR pattern**. HAL+JSON App resources are
paired with Qiq Page resources for public HTML and a minimal Article
admin protected by Google OAuth, session auth, author ownership, and CSRF
form guards. Designed so that an AI (or human) reading the codebase can
learn the canonical naming, structure, and flow.

Five entities: Article, Category, Tag, Author, Media. Read + Write
across each, plus an Auth resource (Google OAuth via league/oauth2-google,
swappable to FakeAuthProvider in tests).

---

## How to verify it works in 60 seconds

```bash
composer install
composer fake               # regenerate var/fake/*.json (deterministic, mt_srand(42))
composer schema             # regenerate var/json_schema/*.json from the fake
composer test               # full PHPUnit suite; integration auto-skips
                            # when MySQL is unreachable
composer demo               # 7-section walkthrough that exercises everything
                            # (auto-detects malt → docker → sqlite for the real-DB section)
composer serve              # Qiq/Page HTML at http://127.0.0.1:8081
composer serve:api          # HAL JSON API at http://127.0.0.1:8080
```

Real DB setup: `composer malt:up` (macOS), `composer docker:up`
(cross-platform), or `composer sqlite:up` (CI / no docker).
`composer docker:up` starts only MySQL; async runtime containers are explicit
via `composer parallel:up` and `composer swoole:up`.

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
  BEAR.Resource 1.x-dev / Ray.InputQuery 1.1 supports native `array`
  and `array|null` Input DTO fields; malformed non-array shapes are
  wrapped as `ParameterException` (→ 400) before the DTO constructor
  runs. See `conventions.md` §4 "Native array DTO inputs" and
  `src/Input/ArticleCreateInput.php` for the canonical pattern.
- App contexts:
  - `hal-api-app` — production HTTP
  - `html-hal-app` — Qiq/Page HTML HTTP
  - `cli-hal-api-app` — `bin/app.php`, `composer app`
  - `cli-html-hal-app` — `bin/page.php`, `composer page`
  - `fake-hal-api-app` — dev runtime against `tests/Fake/FakeSqlQuery.php`
  - `test-hal-api-app` — PHPUnit (unit suites)
  - `html-test-hal-api-app` — PHPUnit Page/Qiq suites against FakeSqlQuery
- `composer demo` is the entry point for verifying any change end-to-end.
- **Async embed (Step 4.5 in `composer demo`)**: `bin/async.php` +
  `composer async` opt into parallel `#[Embed]` execution via `bear/async`
  0.3.x. AppModule is unchanged — the library bootstrap overlays
  `ParallelRuntimeModule` on the standard injector. The reference
  parallelisation site is `src/Resource/App/Article.php:38-40`
  (`author` / `category` / `tagList` — three independent embeds). The
  entrypoint requires ext-parallel + ZTS PHP; without it
  `BEAR\Async\Exception\ExtensionNotLoadedException` fires immediately
  with the install instructions, and `bin/app.php` continues to serve
  the sync path unchanged. `composer demo` treats this as a smoke check, not
  a benchmark; process startup dominates fork-per-run timing.

---

## Open upstream issues (might land between sessions)

Still open as of 2026-06-01:

| Issue | Repository | Topic |
|-------|-----------|-------|
| [#76](https://github.com/bearsunday/BEAR.ApiDoc/issues/76) | BEAR.ApiDoc | Pull data from semantic-ex artifacts (ALPS, JSON Schema, fake data) into generated docs |

If it lands, BEAR.Cms can adopt richer `composer doc` output; likely no
project-side change is needed beyond bumping the package version.

---

## Known gaps (deferred deliberately)

| Item | Why deferred | Recovery |
|------|------|---------|
| **Step 6: `#[CacheableResponse]` on all reads** | Partially landed in PR-C2: `Articles` / `Categories` have class-level `#[CacheableResponse]`, and `Article` / `Category` writes carry `#[Purge]`. Entity resources and embedded `Tags` are intentionally excluded — see scope.md D1 for the two upstream behaviours that make those cases unsafe | If the upstream `DonutCommandInterceptor` stops re-running `onGet` on deleted entities and the html-context renderer pipeline handles App-only resources, broaden the attribute to entity reads |
| **phpstan baseline (2 entries)** | One vendor-interface return-type mismatch in `tests/Fake/FakeSqlQuery.php` (`getRowList` returns `list<object>` but `SqlQueryInterface` declares `array<array<mixed>>`); one OAuth provider arg-type widening. Both intentionally suppressed — see comment in `phpstan-baseline.neon`. | Wait for upstream `SqlQueryInterface` to relax its return type; then drop the entry |
| **Async Docker CI smoke** | Docker runtimes exist locally, but GitHub Actions does not yet run `composer parallel:up && composer parallel:demo` | Add a focused workflow once the ext-parallel image build time and cache behaviour are acceptable |
| **Real Google OAuth verification** | Code uses `league/oauth2-google` correctly but no integration test against real Google (needs creds + callback URL) | Add `tests/Integration/AuthGoogleTest.php` that skips unless `GOOGLE_CLIENT_ID` is set |

Write-side CLI generation was previously listed here as a deferred gap. It is now a by-design omission: `article-show` / `article-list` already demonstrate the `#[Cli]` pattern, and `Article::onPost` / `onPut` use `#[Input]` DTOs that `bear/cli` does not map to scalar `#[Option]` commands. See `docs/scope.md` “By design”.

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

- MySQL is listening on 127.0.0.1:3306 as root with no password, but the
  `bear_cms` database is not currently present. `composer malt:up`
  recreates it if you need it.
