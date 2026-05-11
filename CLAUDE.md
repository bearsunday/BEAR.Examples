# Notes for future Claude Code sessions

## Project shape

Namespace `MyVendor\Cms\`. PSR-4 both under `src/` and `tests/`.
Primary surface is the HAL+JSON App API. `src/Resource/Page/*` +
`templates/Page/*` provide Qiq HTML: public pages are read-only, and
`src/Resource/Page/Admin/*` adds article create/update/delete forms
that wrap the App resources. No JS frontend yet. Admin pages are
currently unauthenticated — the typed `UserInterface` /
`AdminUserInterface` boundary is designed in
`docs/journal/auth-boundary-plan.md` and will land in a follow-up PR.

## Contexts

- `hal-api-app` / `cli-hal-api-app` — real DB via AuraSqlModule + MediaQuery.
- `fake-hal-api-app` — same app, SqlQueryInterface overridden to FakeSqlQuery.
  Works with no DB.
- `test-hal-api-app` — loaded by PHPUnit via `AbstractAppTestCase`.
- `html-hal-app` / `cli-html-hal-app` — Qiq/Page HTML over the real DB.
- `html-test-hal-api-app` — Page tests rendered with HtmlModule + FakeSqlQuery.
- `slow-fake-hal-api-app` — demo-only overlay that adds a 150ms delay to the
  three resources embedded by `Article::onGet`. Used by `bin/demo-async.php`.

Switching contexts loads/removes modules by keyword prefix.

Parallel `#[Embed]` execution is enabled at the **entrypoint**, not via a
context prefix. `bin/async.php` hands off to `vendor/bear/async/bootstrap.php`,
which overlays the ext-parallel runtime on top of the same `AppModule`. See
`docs/architecture.md` for the rationale.

## Running things quickly

- Tests (no DB): `vendor/bin/phpunit`
- Async demo/test (PHP ZTS + ext-parallel via Docker):
  ```bash
  composer docker:async-demo
  composer docker:async-test
  ```
- Fake CLI demo:
  ```php
  $r = MyVendor\Cms\Injector::getInstance('fake-hal-api-app')
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
