# Architecture

[日本語](ja/architecture.md)

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
SQL files — production backend; Fake and real produce the same App body shape
   ↓
Page resources + Qiq templates — HTML projection (public read-only; Page/Admin/* wraps the App resources for write forms)
```

Each step is testable in isolation. Tests can run on Fake (fast, hermetic)
or real (DB) — both exercise the same Resource code.

## BDR pattern (Bound / Domain / Resource)

See [BDR_PATTERN-ja.md](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN-ja.md).

| Layer     | Directory            | Role                                               |
|-----------|----------------------|----------------------------------------------------|
| Bound     | `src/Resource/App/*` | HTTP method binding, Link/Embed, validation gates  |
|           | `src/Resource/Page/*` | Qiq/Page HTML — public read-only; `Page/Admin/*` wraps App resources for write forms |
| Domain    | `src/Entity/*`       | Final readonly classes: invariant data             |
| Resource  | `src/Query/*`        | `#[DbQuery]` Read interfaces → entity              |
|           | `src/Query/*`        | `#[DbQuery]` Write interfaces → `void`             |

Page resources reference App resources (`app://`) rather than owning
domain state — the **Reachability** principle. A Page reads the App so
the information stays reachable from the HAL API, CLI, `#[Embed]`,
`#[Link]`, `#[Cacheable]`, JSON Schema, and ALPS; only pure presentation
derivatives are owned by the Page. See
[conventions.md §4 Reachability](conventions.md#reachability--page-reads-app).

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

## Why bySlug / byEmail / byFilename

After `INSERT`, we need the row's new id. Rather than leak lastInsertId
(which differs across drivers and is awkward to fake), each Resource
`onPost` calls a `by<NaturalKey>` method using the natural unique key
the client just supplied. This is portable (SQLite/MySQL/Postgres),
fakeable, and keeps the Command interface `void`-returning. See
`docs/conventions.md` §3 for the broader `item` / `by<NaturalKey>` /
`list` query-naming rule.

## Contexts

BEAR.Sunday's `prod-hal-api-app` / `test-hal-api-app` convention is used
as-is. The additions:
- `fake-hal-api-app` — runtime context that installs FakeModule. Lets the
  app run with no DB (e.g. for demos).
- `test-hal-api-app` — TestModule composes FakeModule.
- `html-hal-app` / `cli-html-hal-app` — real-DB Qiq/Page HTML contexts.
- `html-test-hal-api-app` — PHPUnit Page context; composes TestModule and
  HtmlModule so HTML tests render against FakeSqlQuery.

## What was intentionally *not* built

- A full production admin security model. `Page/Admin/*` is protected by
  `AdminGuard`, `UserInterface` / `AdminUserInterface`, session-backed OAuth
  login, and CSRF form protection, but richer roles beyond author-scoped
  ownership are outside this reference slice.
- JavaScript-enhanced admin interactions.
- Query-string cache invalidation beyond canonical collection URIs. Custom
  filter-variant invalidators are valid application policy, but this reference
  keeps cache examples focused on reusable BEAR.Sunday primitives.
- Full UI pager rendering customization. Article collections already use
  Ray.MediaQuery `#[Pager]`; Page templates still render compact previous
  and next links themselves.

## See also

[conventions.md](conventions.md) — the "how to write code in this
codebase" companion. Naming rules, body construction style, HAL rel
naming split (Choreography vs Taxonomy), file layout, etc.
