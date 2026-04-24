# BEAR.Cms

Reference CMS built on [BEAR.Sunday](https://bearsunday.github.io/).
App-resource only (no admin UI, no frontend): pure HAL+JSON over Ray.MediaQuery.

Entities: Article, Category, Tag, Author, Media.
Read + Write (GET/POST/PUT/DELETE) across every resource.

## How it was built

Semantic-driven, resolution-increasing pipeline:

1. **ALPS profile** — semantic model in [var/alps/profile.json](var/alps/profile.json).
2. **Fake data (semantic-ex)** — 50 realistic records per entity in [var/fake/](var/fake) with referential integrity.
3. **JSON Schema from observed data** — [var/schema/](var/schema) is derived from the fake, not decided up front.
4. **Read path** — readonly entities (`src/Entity/`) + `#[DbQuery]` interfaces (`src/Query/`) + FakeSqlQuery (`src/Fake/`).
5. **Write path** — `#[DbQuery]` command interfaces (`src/Command/`) fronted by Resource `onPost/onPut/onDelete`.
6. **Real DB** — Doctrine Migrations + seed script load the same fake data into a real backend, with matching SQL in `var/db/sql/`.

See [docs/architecture.md](docs/architecture.md) for details.

## Setup

### Fake (no database)

```bash
composer install
php -r 'require "autoload.php"; $r = MyVendor\Cms\Injector::getInstance("fake-hal-api-app")->getInstance(BEAR\Resource\ResourceInterface::class); echo json_encode($r->get("app://self/article", ["id" => 1])->body, JSON_PRETTY_PRINT);'
```

### Real database (via Malt + MySQL)

```bash
# 1. Install Malt (https://koriym.github.io/homebrew-malt/)
brew tap koriym/malt
brew install malt

# 2. Start services declared in malt.json (php-fpm, mysql, nginx)
malt install && malt create && malt start
source <(malt env)

# 3. Configure DB connection
cp .env.dist .env
# edit DB_DSN / DB_USER / DB_PASSWORD

# 4. Migrate and seed
vendor/bin/doctrine-migrations migrate --no-interaction
php bin/seed.php

# 5. Serve
composer serve  # http://127.0.0.1:8080
```

A lightweight alternative for CI / quick trials:
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
| `test-hal-api-app`    | PHPUnit (test suite `resource`)                    | no          |

Context switching is built into BEAR.Sunday's module resolution:
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

## Tests

```bash
vendor/bin/phpunit
```

Runs against FakeSqlQuery — no DB needed.

## Links

- [BEAR.Sunday manual](https://bearsunday.github.io/manuals/1.0/en/index.html)
- [Ray.MediaQuery](https://github.com/ray-di/Ray.MediaQuery)
- [Malt](https://koriym.github.io/homebrew-malt/)
- [BEAR.Skills](https://github.com/bearsunday/BEAR.Skills)
- [ALPS](https://alps.io/)
