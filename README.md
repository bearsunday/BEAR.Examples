# BEAR.Cms

## Description

`BEAR.Cms` is a BEAR.Sunday reference CMS built as a small HAL+JSON
application over Ray.MediaQuery. It is intentionally app resource only:
no admin UI, no frontend, and no controller layer.

The project exists as a conformance example for BEAR.Sunday-aligned
resource applications. It shows the BDR layout, resource naming,
Read/Write query split, ALPS-driven semantics, JSON Schema validation,
fake data, generated API docs, CLI generation, and a real database path
in one compact codebase.

### Resource Surface

| Resource | Methods |
|---|---|
| `app://self/article{?id}` | GET, POST, PUT, DELETE |
| `app://self/articles{?page,perPage,categoryId,tagId,status}` | GET |
| `app://self/category{?id}` | GET, POST, PUT, DELETE |
| `app://self/categories` | GET |
| `app://self/tag{?id}` | GET, POST, DELETE |
| `app://self/tags{?articleId}` | GET |
| `app://self/author{?id}` | GET, POST, PUT |
| `app://self/media{?id}` | GET, POST, DELETE |
| `app://self/auth` | GET, POST |

### Construction Model

This repo was built by increasing resolution from semantics to runtime:

1. **ALPS profile**: semantic model in [var/alps/profile.json](var/alps/profile.json).
2. **Fake data**: 50 realistic records per entity in [var/fake/](var/fake), with referential integrity.
3. **JSON Schema**: [var/json_schema/](var/json_schema) generated from observed fake data.
4. **Read path**: readonly entities in [src/Entity/](src/Entity/), `#[DbQuery]` interfaces in [src/Query/](src/Query/), and `FakeSqlQuery` in [tests/Fake/](tests/Fake/).
5. **Write path**: command interfaces in `src/Query/*CommandInterface.php`, called from resource `onPost`, `onPut`, and `onDelete` methods.
6. **Real DB path**: Doctrine Migrations, seed script, and SQL files in [var/db/sql/](var/db/sql/).
7. **Hypermedia**: `#[Embed]`, `addQuery()`, and `#[JsonSchema]` shape HAL bodies and validation.

For the architectural rationale, read [docs/architecture.md](docs/architecture.md).

## Setup

### Fake Runtime

Use this path when you want to inspect the app without a database.

```bash
composer install
php -r 'require "autoload.php"; $r = MyVendor\Cms\Injector::getInstance("fake-hal-api-app")->getInstance(BEAR\Resource\ResourceInterface::class); echo json_encode($r->get("app://self/article", ["id" => 1])->body, JSON_PRETTY_PRINT), PHP_EOL;'
```

### Malt Database

Use this path on macOS when you want the real MySQL-backed app.

```bash
brew tap koriym/malt && brew install malt
cp .env.dist .env
composer malt:up
composer serve
```

The server runs at `http://127.0.0.1:8080`.

### Docker Database

Use this path when Docker is the local database provider.

```bash
cp .env.dist .env       # set DB_PASSWORD=root
composer docker:up
composer serve
```

### SQLite Trial

Use this path for a quick database-backed trial or CI-style local check.

```bash
composer sqlite:up
DB_DSN="sqlite:/tmp/bear_cms.db" composer serve
```

### Runtime Contexts

| Context | Purpose | DB required |
|---|---|---|
| `hal-api-app` | Production HTTP | yes |
| `cli-hal-api-app` | `composer app`, [bin/app.php](bin/app.php), and generated `bin/cli/*` scripts | yes |
| `fake-hal-api-app` | Dev runtime against `FakeSqlQuery` | no |
| `test-hal-api-app` | PHPUnit | no |

`fake-` prepends [src/Module/FakeModule.php](src/Module/FakeModule.php).
`test-` prepends [src/Module/TestModule.php](src/Module/TestModule.php).

### Commands

```bash
composer test       # PHPUnit against the fake test context
composer tests      # coding standard, static analysis, PHPMD, PHPUnit
composer fake       # regenerate var/fake/*.json
composer schema     # regenerate var/json_schema/*.json from fake data
composer semantic   # fake then schema
composer doc        # regenerate docs/index.html, openapi.json, llms.txt
composer cli        # regenerate bin/cli/* from #[Cli] attributes
composer serve      # PHP built-in server on 127.0.0.1:8080
```

Generated CLI commands live under `bin/cli/`:

```bash
composer cli
bin/cli/article-show -i 1
bin/cli/article-list -s published -n 5
```

The integration tests in [tests/Integration/](tests/Integration/) skip
unless MySQL is reachable. Start MySQL with `composer malt:up` or
`composer docker:up` when you want those tests included.

## Reference

This README follows [README spec v1](docs/readme-spec.md): Description,
Setup, Reference, Links. Read the project in this order:

1. [README.md](README.md) - project identity, setup paths, and reference map.
2. [docs/readme-spec.md](docs/readme-spec.md) - the shared README frame for BEAR.Sunday-aligned projects.
3. [docs/en/reading-guide.md](docs/en/reading-guide.md) ([Japanese](docs/ja/reading-guide.md)) - where to start reading the code and what to notice by layer.
4. [docs/architecture.md](docs/architecture.md) - BDR layout, dispatch behavior, and design rationale.
5. [docs/conventions.md](docs/conventions.md) - canonical rulebook for naming, resource patterns, Read/Write SQL contracts, and tests.
6. [docs/resources.md](docs/resources.md) - URI map and body shapes.
7. [docs/index.html](docs/index.html) - generated URI, request, response, `_links`, and `_embedded` map.
8. [docs/alps.md](docs/alps.md) and [var/alps/profile.json](var/alps/profile.json) - semantic source of truth.
9. [tests/Fake/FakeSqlQuery.php](tests/Fake/FakeSqlQuery.php) - executable dispatch contract for fake and test contexts.
10. [docs/journal/build-log.md](docs/journal/build-log.md), [docs/journal/decisions-to-consult.md](docs/journal/decisions-to-consult.md), and [docs/journal/handoff.md](docs/journal/handoff.md) - background history, not runtime contract.

### Index By Question

| If you are asking... | Start here |
|---|---|
| What should a BEAR.Sunday reference README look like? | [readme-spec.md](docs/readme-spec.md) |
| Where should I start reading the code? | [docs/en/reading-guide.md](docs/en/reading-guide.md) / [Japanese](docs/ja/reading-guide.md) |
| What do I name a class, method, SQL file, or property? | [conventions.md Section 3](docs/conventions.md#3-naming) |
| How do I shape a resource body, status, embed, or link? | [conventions.md Section 4](docs/conventions.md#4-resource-patterns) |
| Should an endpoint use scalar params or an Input DTO? | [conventions.md Section 4](docs/conventions.md#input-shape--validation) |
| How do Read and Write share an entity? | [conventions.md Section 5](docs/conventions.md#5-readwrite-sql-contract) |
| What are the generated API shapes? | [docs/index.html](docs/index.html), [docs/resources.md](docs/resources.md), and [docs/openapi.json](docs/openapi.json) |
| Where is the semantic model? | [docs/alps.md](docs/alps.md) and [var/alps/profile.json](var/alps/profile.json) |
| Why was a design choice made? | [docs/journal/decisions-to-consult.md](docs/journal/decisions-to-consult.md) |
| What changed phase by phase? | [docs/journal/build-log.md](docs/journal/build-log.md) |

## Links

- [BEAR.Sunday manual](https://bearsunday.github.io/manuals/1.0/en/index.html)
- [BEAR.Sunday repository](https://github.com/bearsunday/BEAR.Sunday)
- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery)
- [BEAR.ApiDoc](https://github.com/bearsunday/BEAR.ApiDoc)
- [BEAR.Cli](https://github.com/bearsunday/BEAR.Cli)
- [Malt](https://koriym.github.io/homebrew-malt/)
- [BEAR.Skills](https://github.com/bearsunday/BEAR.Skills)
- [ALPS](https://alps.io/)
- [Generated OpenAPI document](docs/openapi.json)
- [Generated LLM reference](docs/llms.txt)
