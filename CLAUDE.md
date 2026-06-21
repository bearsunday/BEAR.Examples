# Notes for future Claude Code sessions

## Project shape

Namespace `BEAR\Examples\`. PSR-4 both under `src/` and `tests/`.
Primary surface is the HAL+JSON App API. `src/Resource/Page/*` +
`templates/Page/*` provide Qiq HTML: public pages are read-only, and
`src/Resource/Page/Admin/*` adds article create/update/delete forms
that wrap the App resources. No JS frontend yet. Admin pages are protected
by the typed `UserInterface` / `AdminUserInterface` boundary and a
session-backed OAuth login flow (`/admin/login` → `/admin/callback`).

## Contexts

- `hal-api-app` / `cli-hal-api-app` — real DB via AuraSqlModule + MediaQuery.
- `fake-hal-api-app` — same app, SqlQueryInterface overridden to FakeSqlQuery.
  Works with no DB and binds a deterministic admin user for demos.
- `test-hal-api-app` — loaded by PHPUnit via `AbstractAppTestCase`.
- `html-hal-app` / `cli-html-hal-app` — Qiq/Page HTML over the real DB.
- `html-test-hal-api-app` — Page tests rendered with HtmlModule + FakeSqlQuery;
  defaults to `Visitor`, with explicit fake admin session overrides in admin tests.

Switching contexts loads/removes modules by keyword prefix; see
`src/Module/{App,Fake,Test}Module.php`.

## Running things quickly

- Tests (no DB): `vendor/bin/phpunit`
- Fake CLI demo:
  ```php
  $r = BEAR\Examples\Injector::getInstance('fake-hal-api-app')
      ->getInstance(BEAR\Resource\ResourceInterface::class);
  var_dump($r->get('app://self/article', ['id' => 1])->body);
  ```
- Real CLI demo (SQLite):
  ```bash
  rm -f /tmp/bear_cms.db
  DB_DSN='sqlite:/tmp/bear_cms.db' vendor/bin/doctrine-migrations migrate --no-interaction
  DB_DSN='sqlite:/tmp/bear_cms.db' php bin/seed.php
  ```

## Gotchas

- `DbQueryInterceptor` routes **every** `#[DbQuery]` call to getRow or
  getRowList by return type. `exec()` on SqlQueryInterface is *not* used
  by the interceptor. FakeSqlQuery handles write SQL ids inside
  getRow/getRowList accordingly.
- Ray.MediaQuery's `FetchNewInstance` uses `PDO::FETCH_FUNC`, so SELECT
  column order must match the entity's `__construct` positional args. If
  you add a column, keep the SELECT and the constructor aligned.
- Writes return `void` from Command methods. To get the new id back, use
  the entity's natural unique key (slug/email/filename) via a
  `by<NaturalKey>` query (`bySlug` / `byEmail` / `byFilename`) — see
  `Article::onPost`. Query method naming is codified in
  `docs/conventions.md` §3 (`item` for PK, `by<Key>` for natural key,
  `list` for collections).
- When module bindings change, clear the DI cache for every context:
  `rm -rf var/tmp/*-hal-app var/tmp/*-hal-api-app`.

## Regenerating fakes and schemas

```bash
php bin/semantic-ex/gen-fake.php         # writes var/fake/*.json
php bin/semantic-ex/gen-schemas.php      # writes var/json_schema/*.json
```

Both scripts are deterministic (`mt_srand(42)`).
