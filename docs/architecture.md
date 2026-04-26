# Architecture

## Resolution-increasing build-up

The code is built bottom-up, each phase adding resolution:

```
ALPS (semantics)
   ↓ alps-skills:alps
Fake data (50/entity, with referential integrity)
   ↓ be-framework-skills:semantic-ex
JSON Schema (constraints derived from observation, not decided)
   ↓
BDR code — readonly entities + #[DbQuery] interfaces
   ↓
FakeSqlQuery (in-memory) — full Read+Write stack runs without a DB
   ↓
Doctrine Migrations + seed — real schema + same seed data
   ↓
SQL files — production backend; Fake and real produce the same body shape
```

Each step is testable in isolation. Tests can run on Fake (fast, hermetic)
or real (DB) — both exercise the same Resource code.

## BDR pattern (Bound / Domain / Resource)

See [BDR_PATTERN-ja.md](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN-ja.md).

| Layer     | Directory            | Role                                               |
|-----------|----------------------|----------------------------------------------------|
| Bound     | `src/Resource/App/*` | HTTP method binding, Link/Embed, validation gates  |
| Domain    | `src/Entity/*`       | Final readonly classes: invariant data             |
| Resource  | `src/Query/*`        | `#[DbQuery]` Read interfaces → entity              |
|           | `src/Command/*`      | `#[DbQuery]` Write interfaces → `void`             |

Factories are not used here: the simplest path is `FetchNewInstance` via
PDO::FETCH_FUNC, which constructs the entity positionally from the SELECT
column order. SQL files in `var/db/sql/` therefore project columns in the
exact order each entity's `__construct` expects.

## Read vs Write dispatch

`DbQueryInterceptor` routes every `#[DbQuery]` method through
`SqlQueryInterface::getRow` or `getRowList` based on the return type —
`exec()` is *not* called by the interceptor. For Ray.MediaQuery's real
`SqlQuery`, this is fine: `perform()` runs the statement regardless and
returns `[]` for non-SELECT. For `FakeSqlQuery` we follow the same
convention and dispatch writes inside `getRow`/`getRowList`.

## Why getBySlug / getByEmail / getByFilename

After `INSERT`, we need the row's new id. Rather than leak lastInsertId
(which differs across drivers and is awkward to fake), each Resource
`onPost` calls a `getBy*` method using the natural unique key the client
just supplied. This is portable (SQLite/MySQL/Postgres), fakeable, and
keeps the Command interface `void`-returning.

## Contexts

BEAR.Sunday's `prod-hal-api-app` / `test-hal-api-app` convention is used
as-is. The additions:
- `fake-hal-api-app` — runtime context that installs FakeModule. Lets the
  app run with no DB (e.g. for demos).
- `test-hal-api-app` — TestModule composes FakeModule.

## What was intentionally *not* built

- Admin UI / HTML / JS
- Authentication / authorisation
- Cache invalidation (`#[Cacheable]`, `#[Purge]`) — left as a hook-in
  point; not needed for a reference
- `#[Pager]` / `PagesInterface` — deferred to avoid faking Pagerfanta's
  PDO-backed Pages. Filtering + `page`/`perPage`/`count` handled at the
  Resource layer.

## See also

[conventions.md](conventions.md) — the "how to write code in this
codebase" companion. Naming rules, body construction style, HAL rel
naming split (Choreography vs Taxonomy), file layout, etc.
