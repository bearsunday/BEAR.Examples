# Notes for future Claude Code sessions

## Project shape

Namespace `MyVendor\Cms\`. PSR-4 both under `src/` and `tests/`.
Primary surface is the HAL+JSON App API; `src/Resource/Page/*` and
`templates/Page/*` provide read-only Qiq HTML. No admin write UI or JS frontend yet.

## Contexts

- `hal-api-app` / `cli-hal-api-app` — real DB via AuraSqlModule + MediaQuery.
- `fake-hal-api-app` — same app, SqlQueryInterface overridden to FakeSqlQuery.
  Works with no DB.
- `test-hal-api-app` — loaded by PHPUnit via `AbstractAppTestCase`.
- `html-hal-app` / `cli-html-hal-app` — Qiq/Page HTML over the real DB.
- `html-test-hal-api-app` — Page tests rendered with HtmlModule + FakeSqlQuery.

Switching contexts loads/removes modules by keyword prefix; see
`src/Module/{App,Fake,Test}Module.php`.

## Running things quickly

- Tests (no DB): `vendor/bin/phpunit`
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
- When module bindings change, clear the DI cache:
  `rm -rf var/tmp/{fake-,test-,}hal-api-app`.

## Regenerating fakes and schemas

```bash
php bin/semantic-ex/gen-fake.php         # writes var/fake/*.json
php bin/semantic-ex/gen-schemas.php      # writes var/json_schema/*.json
```

Both scripts are deterministic (`mt_srand(42)`).
