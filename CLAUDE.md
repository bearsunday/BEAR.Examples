# Notes for future Claude Code sessions

## Implementing a pattern? Start at the Kata index

To learn or transplant an implementation pattern ("how do I add a pager /
HAL embed / streaming response / PRG form / Cacheable resource"), start at
`docs/source-index.md` — the Kata (型) source index. Each Kata maps an intent
to canonical Source, Tests, a **着手前チェック (before)**, and a
**マスター確認 (after)** checklist. The `bear-kata` skill
(`.claude/skills/bear-kata/SKILL.md`) routes intent → Kata. Prefer
`Status: canonical` Katas as the form to copy; `comparison-only` Katas are
for understanding only, not for transplanting.

## Project shape

Namespace `BEAR\Kata\`. PSR-4 both under `src/` and `tests/`.
Primary surface is the HAL+JSON App API. `src/Resource/Page/*` +
`templates/Page/*` provide Qiq HTML: public pages are read-only, and
`src/Resource/Page/Admin/*` adds article create/update/delete forms
that wrap the App resources. No JS frontend yet. Admin pages are protected
by the typed `UserInterface` / `AdminUserInterface` boundary and a
session-backed OAuth login flow (`/admin/login` → `/admin/callback`).
Page resources reference App (they do not own domain state) — the
Reachability principle, see `docs/conventions.md` §4 Reachability.

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
  $r = BEAR\Kata\Injector::getInstance('fake-hal-api-app')
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

- `DbQueryInterceptor` routes `#[DbQuery]` calls three ways: `#[Pager]`
  methods go to getPages/getCount, `PostQueryInterface` return types
  (`InsertedRow`/`AffectedRows`) go to execPostQuery, and everything else
  to getRow or getRowList by return type. `exec()` on SqlQueryInterface
  is *not* used by the interceptor. FakeSqlQuery handles write SQL ids
  inside getRow/getRowList accordingly.
- Ray.MediaQuery hydrates via `PDO::FETCH_FUNC`, so SELECT column order
  must match the hydration target's positional args: the entity's
  `__construct` for plain `#[DbQuery]` (Author etc.), or the factory
  method for `#[DbQuery(factory: ...)]` (Article uses `ArticleFactory`).
  If you add a column, keep the SELECT and the constructor/factory aligned.
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
