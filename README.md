# MyVendor.Cms

## Description

MyVendor.Cms is a reference CMS built on
[BEAR.Sunday](https://bearsunday.github.io/).

It demonstrates HAL+JSON App resources over Ray.MediaQuery, Qiq Page
resources for reader-facing HTML, and a minimal Article admin. The domain
has five entities: Article, Category, Tag, Author, and Media. Every App
resource has read/write coverage where meaningful, plus an Auth resource
for the Google OAuth flow.

## Background

This repository is a working teaching artefact: BEAR.Sunday's canonical
naming, structure, and flow rendered as a small CMS that humans and AI
assistants can read as a template.

Framework conventions are easiest to learn from a complete executable
example. The code is intentionally small, but each naming, module,
Resource, Query, SQL, validation, fake, and test choice is meant to be
copied deliberately.

The implementation was built as a semantic-driven, resolution-increasing
pipeline:

1. **ALPS profile** — semantic model in
   [var/alps/profile.json](var/alps/profile.json).
2. **Fake data (semantic-ex)** — 50 realistic records per entity in
   [var/fake/](var/fake), with referential integrity.
3. **JSON Schema from observed data** —
   [var/json_schema/](var/json_schema) is derived from the fake, not
   decided up front.
4. **Read path** — readonly entities (`src/Entity/`), `#[DbQuery]`
   interfaces (`src/Query/`), and `tests/Fake/FakeSqlQuery.php`.
5. **Write path** — Command interfaces (`src/Query/*CommandInterface.php`)
   fronted by Resource `onPost`, `onPut`, and `onDelete` methods.
6. **Real DB** — Doctrine Migrations and the seed script load the same fake
   data into a real backend, with matching SQL in `var/db/sql/`.
7. **Hypermedia** — `#[Link]`, `#[Embed]`, and `addQuery()` materialise
   HAL `_links` and `_embedded`; `#[JsonSchema]` validates request and
   response bodies.
8. **Qiq HTML** — Page resources render public pages and the Article admin
   without JavaScript.

See [docs/architecture.md](docs/architecture.md) for the BDR layout and
design rationale.

## Scope

This project demonstrates BEAR.Sunday application-resource and page-resource
design. It is not intended to be a production-ready CMS.

In scope:

- App resources with HAL links/embeds, JSON Schema validation, and
  Ray.MediaQuery read/write contracts.
- Page resources using Qiq for public HTML and a small local Article admin.
- Fake/real DB parity, deterministic semantic data, and MySQL/SQLite setup.
- Read/write naming conventions, SQL filename conventions, and Resource
  body/status patterns.
- Tests that pin status codes, body shapes, links, embeds, schema behavior,
  fakes, and representative real-DB paths.

Intentionally not the focus:

- A full production admin UI.
- JavaScript-enhanced editing flows.
- Production authorization policy for the demo admin.
- Exhaustive CRUD symmetry where it would only repeat an already-shown
  pattern.

For the detailed in/out list and deferred items, read
[docs/scope.md](docs/scope.md).

## Requirements

- PHP 8.5
- Composer
- Optional: MySQL via Malt or docker-compose
- Optional: SQLite for quick real-DB trials

## Quick Start

Use the fake context first. It needs no database.

```bash
composer install
composer fake
php -r 'require "autoload.php"; $r = MyVendor\Cms\Injector::getInstance("fake-hal-api-app")->getInstance(BEAR\Resource\ResourceInterface::class); echo json_encode($r->get("app://self/article", ["id" => 1])->body, JSON_PRETTY_PRINT);'
```

Run the main test suite:

```bash
composer test
```

## Setup

### Real database via Malt (macOS)

```bash
brew tap koriym/malt && brew install malt
malt install && malt create && malt start
source <(malt env)

cp .env.dist .env       # edit DB_DSN / DB_USER / DB_PASSWORD
vendor/bin/doctrine-migrations migrate --no-interaction
php bin/seed.php
composer serve:api      # HAL JSON API at http://127.0.0.1:8080
composer serve          # Qiq/Page HTML at http://127.0.0.1:8081
```

### Real database via docker-compose (cross-platform)

```bash
docker compose up -d
cp .env.dist .env
DB_DSN='mysql:host=127.0.0.1;dbname=bear_cms;charset=utf8mb4' DB_USER=root DB_PASSWORD=root \
  vendor/bin/doctrine-migrations migrate --no-interaction
DB_DSN='mysql:host=127.0.0.1;dbname=bear_cms;charset=utf8mb4' DB_USER=root DB_PASSWORD=root \
  php bin/seed.php
```

### Real database via SQLite (CI / quick trials)

```bash
rm -f /tmp/bear_cms.db
DB_DSN="sqlite:/tmp/bear_cms.db" vendor/bin/doctrine-migrations migrate --no-interaction
DB_DSN="sqlite:/tmp/bear_cms.db" php bin/seed.php
DB_DSN="sqlite:/tmp/bear_cms.db" composer serve         # Qiq/Page HTML on :8081
# or to expose the HAL JSON API on :8080:
# DB_DSN="sqlite:/tmp/bear_cms.db" composer serve:api
```

### Built-in servers

Each server is blocking, so run them in separate terminals:

```bash
# terminal 1
composer serve           # Qiq/Page HTML at http://127.0.0.1:8081

# terminal 2
composer serve:api       # HAL JSON API at http://127.0.0.1:8080
```

## Reference Guide

Read the repository in this order:

1. **[README.md](README.md)** — what the project is and how to run it.
2. **[docs/readme-spec.md](docs/readme-spec.md)** — the shared README frame
   for BEAR.Sunday-aligned reference projects.
3. **[docs/en/reading-guide.md](docs/en/reading-guide.md)**
   ([日本語](docs/ja/reading-guide.md)) — where to start reading the code
   and what to notice by layer.
4. **[docs/architecture.md](docs/architecture.md)** — BDR layout,
   dispatch quirks, and design rationale.
5. **[docs/conventions.md](docs/conventions.md)** — the canonical rulebook:
   naming, Resource patterns, Read/Write SQL contract, and test policy.
   New code patterns land here first.
6. **[docs/scope.md](docs/scope.md)** — what the reference includes,
   omits, and defers.
7. **[docs/alps.md](docs/alps.md)** and
   **[var/alps/profile.json](var/alps/profile.json)** — semantic source of
   truth: Choreography names and Taxonomy nouns.
8. **[docs/journal/build-log.md](docs/journal/build-log.md)** — phase-by-phase
   construction history.
9. **[docs/journal/decisions-to-consult.md](docs/journal/decisions-to-consult.md)**
   — every decision with the discussion that shaped it.
10. **[tests/Fake/FakeSqlQuery.php](tests/Fake/FakeSqlQuery.php)** — the fake
   dispatch contract in executable form.

Index by question:

| If you're asking... | Start here |
|---|---|
| What should a BEAR.Sunday reference README look like? | [docs/readme-spec.md](docs/readme-spec.md) |
| Where should I start reading the code? | [docs/en/reading-guide.md](docs/en/reading-guide.md) / [日本語](docs/ja/reading-guide.md) |
| What do I name a class, method, SQL file, or property? | [conventions.md §3 Naming](docs/conventions.md#3-naming) |
| How do I shape a Resource body, status, embed, or link? | [conventions.md §4 Resource patterns](docs/conventions.md#4-resource-patterns) |
| Where are the MediaQuery pager / SELECT result / AffectedRows examples? | [docs/media-query-samples.md](docs/media-query-samples.md) |
| Scalar params or Input DTO? | [conventions.md §4 Input shape & validation](docs/conventions.md#4-resource-patterns) |
| How do Read and Write share an entity? | [conventions.md §5 Read/Write SQL contract](docs/conventions.md#5-readwrite-sql-contract) |
| What is intentionally not built? | [docs/scope.md](docs/scope.md) |
| Why was this decision made? | [journal/decisions-to-consult.md](docs/journal/decisions-to-consult.md) |
| What changed phase-by-phase? | [journal/build-log.md](docs/journal/build-log.md) |
| What is the next session expected to know? | [journal/handoff.md](docs/journal/handoff.md) |

## API Documentation

Generated API documentation lives under `docs/`:

- [docs/index.html](docs/index.html) — generated URI / request / response /
  `_links` / `_embedded` map.
- [docs/openapi.json](docs/openapi.json) — OpenAPI contract.
- [docs/llms.txt](docs/llms.txt) — LLM-oriented API summary.
- [docs/resources.md](docs/resources.md) — hand-written resource overview.

Regenerate it with:

```bash
composer doc
```

The HAL JSON API is served by `composer serve:api` on
`http://127.0.0.1:8080`. Page resources are served by `composer serve` on
`http://127.0.0.1:8081/`. Public pages include `/`, `/articlelist`, and
`/article?id=1`; the local admin starts at `/admin/index`.

## Runtime Contexts

| Context                    | Purpose                               | DB required |
|----------------------------|---------------------------------------|-------------|
| `hal-api-app`              | Production HAL JSON HTTP              | yes         |
| `html-hal-app`             | Production Qiq/Page HTML HTTP         | yes         |
| `cli-hal-api-app`          | `composer app` / `bin/app.php`        | yes         |
| `cli-html-hal-app`         | `composer page` / `bin/page.php`      | yes         |
| `fake-hal-api-app`         | Dev runtime against FakeSqlQuery      | no          |
| `test-hal-api-app`         | PHPUnit App resource tests            | no          |
| `html-test-hal-api-app`    | PHPUnit Page/Qiq tests                | no          |
| `async-hal-api-app`        | BEAR.Async parallel `#[Embed]` context | yes        |
| `async-test-hal-api-app`   | Async PHPUnit App resource tests      | no          |
| `async-slow-fake-hal-api-app` | Async timing demo with fake data and demo latency | no |

`fake-` prepends [src/Module/FakeModule.php](src/Module/FakeModule.php);
`test-` prepends [src/Module/TestModule.php](src/Module/TestModule.php).
`async-` is handled by [src/Injector.php](src/Injector.php): it overlays
[src/Module/AsyncModule.php](src/Module/AsyncModule.php) on the same context
without the `async-` prefix, and worker threads use that explicit worker
context.

## Development

Useful composer scripts:

```bash
composer test       # PHPUnit suite (integration auto-skips without MySQL)
composer tests      # cs + static analysis + PHPMD + PHPUnit
composer fake       # regenerate var/fake/*.json
composer schema     # regenerate var/json_schema/*.json from fake
composer semantic   # fake then schema (the full semantic-ex pass)
composer doc        # regenerate docs/index.html, docs/openapi.json, docs/llms.txt
composer cli        # regenerate bin/cli/* from #[Cli] attributes
composer serve      # Qiq/Page HTML server on :8081
composer serve:api  # HAL JSON API server on :8080
composer demo:async # async demo; use docker:async-demo when ext-parallel is absent
```

CLI commands generated from `#[Cli]` attributes:

```bash
composer cli
bin/cli/article-show -i 1
bin/cli/article-list -s published -n 5
```

Tests run Resource, Entity, Hypermedia, Smoke, and Integration suites. The
Integration suite (`tests/Integration/`) skips automatically unless MySQL is
reachable; bring it up with `docker compose up -d` or Malt to include it.
The async demo and async contract test require PHP ZTS + `ext-parallel`; run
them with `composer docker:async-demo` and `composer docker:async-test`.
If future PECL `parallel` releases stop building against the PHP ZTS base image,
pin the `parallel` version in `Dockerfile.async`.

## Project Journal

Reflection and decision logs live in [docs/journal/](docs/journal/):
build log, framework critique, skill proposals, and the back-and-forth that
shaped the design choices. They are not part of the API surface, but they
are useful when reading this repository as a reference implementation.

## Links

- [BEAR.Sunday manual](https://bearsunday.github.io/manuals/1.0/en/index.html)
- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery)
- [BEAR.ApiDoc](https://github.com/bearsunday/BEAR.ApiDoc)
- [BEAR.Cli](https://github.com/bearsunday/BEAR.Cli)
- [Malt](https://koriym.github.io/homebrew-malt/)
- [BEAR.Skills](https://github.com/bearsunday/BEAR.Skills)
- [ALPS](https://alps.io/)
