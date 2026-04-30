# BEAR.Cms

Reference CMS built on [BEAR.Sunday](https://bearsunday.github.io/).
App-resource only (no admin UI, no frontend): pure HAL+JSON over Ray.MediaQuery.

Entities: Article, Category, Tag, Author, Media.
Read + Write (GET/POST/PUT/DELETE) across every resource, plus an Auth
resource (Google OAuth).

## How it was built

Semantic-driven, resolution-increasing pipeline:

1. **ALPS profile** — semantic model in [var/alps/profile.json](var/alps/profile.json).
2. **Fake data (semantic-ex)** — 50 realistic records per entity in [var/fake/](var/fake) with referential integrity.
3. **JSON Schema from observed data** — [var/json_schema/](var/json_schema) is derived from the fake, not decided up front.
4. **Read path** — readonly entities (`src/Entity/`) + `#[DbQuery]` interfaces (`src/Query/`) + FakeSqlQuery (`tests/Fake/`).
5. **Write path** — Command interfaces (`src/Query/*CommandInterface.php`) fronted by Resource `onPost/onPut/onDelete`.
6. **Real DB** — Doctrine Migrations + seed script load the same fake data into a real backend, with matching SQL in `var/db/sql/`.
7. **Hypermedia** — `#[Embed]` + `addQuery()` materialise `_embedded`; `#[JsonSchema]` validates response bodies.

See [docs/architecture.md](docs/architecture.md) for the full BDR layout.

## Using this as a reference

This codebase is a working teaching artefact: BEAR.Sunday's canonical
naming, structure, and flow rendered as a single small CMS. Read it
in this order — both humans and AI assistants benefit from the same
sequence:

1. **[README.md](README.md)** (this file) — what the project is, how to run it.
2. **[docs/reading-guide.md](docs/reading-guide.md)** — where to start reading the code and what to notice by layer.
3. **[docs/architecture.md](docs/architecture.md)** — BDR layout, dispatch quirks, design rationale.
4. **[docs/conventions.md](docs/conventions.md)** — *the* canonical rulebook. Naming, resource patterns, Read/Write SQL contract, test policy. New code lands here first.
5. **[docs/resources.md](docs/resources.md)** — URI map and body shapes.
6. **[docs/alps.md](docs/alps.md)** + **[var/alps/profile.json](var/alps/profile.json)** — semantic source of truth (Choreography names, Taxonomy nouns).
7. **[docs/journal/build-log.md](docs/journal/build-log.md)** — phase-by-phase construction history.
8. **[docs/journal/decisions-to-consult.md](docs/journal/decisions-to-consult.md)** — every decision with the discussion that shaped it.
9. **[tests/Fake/FakeSqlQuery.php](tests/Fake/FakeSqlQuery.php)** — reading this teaches the dispatch contract better than any doc.

### Index by question

| If you're asking… | Start here |
|---|---|
| Where should I start reading the code? | [docs/reading-guide.md](docs/reading-guide.md) |
| What do I name a class / method / SQL file / property? | [conventions.md §3 Naming](docs/conventions.md#3-naming) |
| How do I shape a Resource (body, status, embed, link)? | [conventions.md §4 Resource patterns](docs/conventions.md#4-resource-patterns) |
| Scalar params or Input DTO? | [conventions.md §4 "Input shape & validation"](docs/conventions.md#4-resource-patterns) |
| How do Read and Write share an entity? | [conventions.md §5 Read/Write SQL contract](docs/conventions.md#5-readwrite-sql-contract) |
| Why was this decision made? | [journal/decisions-to-consult.md](docs/journal/decisions-to-consult.md) (P0–P8 by topic) |
| What changed phase-by-phase? | [journal/build-log.md](docs/journal/build-log.md) |
| What is the next session expected to know? | [journal/handoff.md](docs/journal/handoff.md) |

## Setup

### Fake (no database)

```bash
composer install
php -r 'require "autoload.php"; $r = MyVendor\Cms\Injector::getInstance("fake-hal-api-app")->getInstance(BEAR\Resource\ResourceInterface::class); echo json_encode($r->get("app://self/article", ["id" => 1])->body, JSON_PRETTY_PRINT);'
```

### Real database via Malt (macOS)

```bash
brew tap koriym/malt && brew install malt
malt install && malt create && malt start
source <(malt env)

cp .env.dist .env       # edit DB_DSN / DB_USER / DB_PASSWORD
vendor/bin/doctrine-migrations migrate --no-interaction
php bin/seed.php
composer serve          # http://127.0.0.1:8080
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
DB_DSN="sqlite:/tmp/bear_cms.db" composer serve
```

## Contexts

| Context               | Purpose                                            | DB required |
|-----------------------|----------------------------------------------------|-------------|
| `hal-api-app`         | Production HTTP                                    | yes         |
| `cli-hal-api-app`     | `composer app` / `bin/app.php`                     | yes         |
| `fake-hal-api-app`    | Dev runtime against FakeSqlQuery                   | no          |
| `test-hal-api-app`    | PHPUnit                                            | no          |

`fake-` prepends [src/Module/FakeModule.php](src/Module/FakeModule.php),
`test-` prepends [src/Module/TestModule.php](src/Module/TestModule.php).

## Resources

See [docs/resources.md](docs/resources.md) for the full URI + schema map.

| URI                                                                | Methods                |
|--------------------------------------------------------------------|------------------------|
| `app://self/article{?id}`                                          | GET, POST, PUT, DELETE |
| `app://self/articles{?page,perPage,categoryId,tagId,status}`       | GET                    |
| `app://self/category{?id}`, `app://self/categories`                | GET[+POST/PUT/DELETE]  |
| `app://self/tag{?id}`, `app://self/tags`                           | GET[+POST/DELETE]      |
| `app://self/author{?id}`                                           | GET, POST, PUT         |
| `app://self/media{?id}`                                            | GET, POST, DELETE      |
| `app://self/auth`                                                  | GET, POST              |

## CLI

`#[Cli]` attributes generate stand-alone CLI commands:

```bash
composer cli                       # regenerate bin/cli/* from current attributes
bin/cli/article-show -i 1
bin/cli/article-list -s published -n 5
```

## Useful composer scripts

```bash
composer test       # PHPUnit (resource + entity + hypermedia + skipped integration)
composer fake       # regenerate var/fake/*.json
composer schema     # regenerate var/json_schema/*.json from fake
composer semantic   # fake then schema (the full semantic-ex pass)
composer doc        # regenerate docs/index.html, docs/openapi.json, docs/llms.txt
composer cli        # regenerate bin/cli/* from #[Cli] attributes
composer serve      # PHP built-in server on :8080
```

## Tests

```bash
vendor/bin/phpunit
```

Runs `resource` + `entity` + `hypermedia` suites against FakeSqlQuery.
The `integration` suite (`tests/Integration/`) skips automatically unless
MySQL is reachable — bring it up with `docker compose up -d` (or malt) to
include it.

## Project journal

Reflection / decision logs live in [docs/journal/](docs/journal/) — phase
build log, framework critique, skill proposals, and the back-and-forth
that shaped the design choices. Not part of the API surface; useful as
narrative for anyone reading this as a reference implementation.

## Links

- [BEAR.Sunday manual](https://bearsunday.github.io/manuals/1.0/en/index.html)
- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery)
- [BEAR.ApiDoc](https://github.com/bearsunday/BEAR.ApiDoc)
- [BEAR.Cli](https://github.com/bearsunday/BEAR.Cli)
- [Malt](https://koriym.github.io/homebrew-malt/)
- [BEAR.Skills](https://github.com/bearsunday/BEAR.Skills)
- [ALPS](https://alps.io/)
