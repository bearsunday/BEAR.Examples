# Conventions

Cross-cutting "how to write code in this codebase" rules. Architecture
and pattern explanations live in [architecture.md](architecture.md);
this file is the companion that codifies the *decisions* made during
construction (see [journal/decisions-to-consult.md](journal/decisions-to-consult.md)
for the original discussion log).

When in doubt, follow what's here. New conventions land here first,
then the code/docs follow.

---

## 1. Code structure

| What | Convention |
|------|-----------|
| Namespace root | `MyVendor\Cms` |
| Layer directories | `src/Entity/`, `src/Query/`, `src/Command/`, `src/Resource/App/`, `src/Module/`, `src/Service/`, `src/Fake/` |
| Fake placement | `src/Fake/` (runtime-usable, not `tests/Fake/` only) |
| Module composition | `FakeModule` provides the binding; `TestModule` *installs* `FakeModule`. Two-stage so prod/cli/fake/test contexts can compose differently |
| Resource placement | `src/Resource/App/<Class>.php` — every URI is a class. No `App/Index.php` unless a "/" entry-point is meaningful |
| Read/Write split | Always two interfaces per entity: `<Entity>QueryInterface` (Read) and `<Entity>CommandInterface` (Write). Never mix |

## 2. Contexts

| Context | Where it runs |
|---------|--------------|
| `hal-api-app` | Production HTTP |
| `cli-hal-api-app` | `bin/app.php`, `composer app`, `bin/cli/*` scripts |
| `fake-hal-api-app` | Runtime against `FakeSqlQuery` (no DB) |
| `test-hal-api-app` | PHPUnit (composes `FakeModule`) |

`fake-` and `test-` are the canonical prefixes; do not invent variants.

## 3. Naming

### Class / interface
- Read interface: `<Entity>QueryInterface` (e.g. `ArticleQueryInterface`)
- Write interface: `<Entity>CommandInterface` (e.g. `ArticleCommandInterface`)
- Entity: `final readonly class` with public properties only

### `getBy{NaturalKey}`
- After INSERT, fetch the new row by natural key, not `lastInsertId`:
  `getBySlug`, `getByEmail`, `getByFilename`. Always include `By` even
  when only one such method exists per entity (consistency with code
  search).

### SQL filenames
- Pattern: `<entity>_<verb>.sql` in `var/db/sql/`
- Verbs: `item` (single read by id), `by_<key>` (read by natural key),
  `list` (multiple reads), `add` (insert), `update`, `delete`, plus
  link-table verbs like `tag_clear`, `tag_link`
- Examples: `article_item.sql`, `article_by_slug.sql`,
  `article_list.sql`, `article_add.sql`, `article_update.sql`,
  `article_delete.sql`

### ALPS Ontology
- Entity-prefixed: `articleId`, `articleSlug`, `articleTitle`,
  `categoryParentId`, `mediaAlt`. Not `id` / `slug` (collision risk
  across entities).

### HAL rel naming — split by ALPS layer
This is the critical rule. ALPS has two distinct layers and HAL has
two distinct collections (`_links` and `_embedded`); align them:

| Where | Source layer | Examples |
|-------|--------------|----------|
| `#[Link]` rel | ALPS **Choreography** (transition verbs) | `goArticleList`, `goAuthor`, `doCreateArticle`, `doDeleteTag` |
| `#[Embed]` rel | ALPS **Taxonomy** (entity nouns) | `author`, `category`, `tagList` |

Do not mix: `#[Embed(rel: 'goAuthor', ...)]` is wrong because `go*` is a
Choreography (client-followable transition), while embed is a
server-included taxonomy instance. Keep the namespaces separate.

## 4. Resource patterns

### Body construction
`$this->body` is the single output channel of a `ResourceObject`. The
`Embed` interceptor injects `Request` objects into `$this->body[$rel]`
*before* `onGet` runs. Therefore:

- **`onGet` with `#[Embed]`**: use `+=` (no-overwrite union). This
  protects the embed-injected slots and makes the intent explicit
  ("add own data, do not touch what was already there"):
  ```php
  $this->body['author']->addQuery(['id' => $article->authorId]);
  $this->body['category']->addQuery(['id' => $article->categoryId]);
  $this->body['tagList']->addQuery(['articleId' => $article->id]);

  $this->body += [
      'id' => $article->id,
      'slug' => $article->slug,
      // ...
  ];
  ```
- **`onGet` without `#[Embed]`, `onPost`, `onPut`, `onDelete`, error
  paths**: literal `$this->body = [...]`. The shape is readable
  top-to-bottom as JSON.
- **Sequential `$this->body['k'] = $v;` is not used.** It hides the
  response shape across many lines and provides no semantic over `+=`
  or literal.

Reasoning: `+` ("union") is "do not overwrite", not "left wins by
priority". When the entity's own fields can never collide with embed
rels (which is enforced by §3 — embeds use taxonomy nouns, body fields
are scalar), `+=` is the most semantically precise operator.

### Status codes
| Method | Success | Not found | Validation fail |
|--------|---------|-----------|-----------------|
| GET | 200 | 404 | n/a |
| POST | 201 + `Location` header | n/a | 422 (via `#[JsonSchema(params:)]`) |
| PUT | 200 | 404 | 422 |
| DELETE | 204 | 404 | n/a |
| Duplicate `slug` (or other unique key) | — | — | 409 (via DB `UniqueConstraintViolation`, no manual catch) |

### After-INSERT id
Never `lastInsertId` (driver-dependent, awkward to fake). Always
re-SELECT via `getBy{NaturalKey}` using the natural key the client
supplied (slug / email / filename). Returns `void` from the `Command`
side.

### Pagination
`#[Pager]` / `PagesInterface` is **not used**. Filtering and counting
happen at the Resource layer, returning
`{ items, page, perPage, count }`. Reason: faking Pagerfanta's
PDO-backed `Pages` is cumbersome and not needed for this reference.

### Input validation
Every `onPost` / `onPut` carries `#[JsonSchema(schema: 'write_response.json', params: '<entity>_<verb>.json')]`.
The `params:` schema lives in `var/json_validate/`.

### Exceptions
- No generic `LogicException` / `RuntimeException`. Define
  `MyVendor\Cms\Exception\<DomainName>Exception` for any thrown
  exception originating in `src/`.
- Read errors (not found) return 404 via `$this->code` — do not throw.

## 5. Read/Write SQL contract

- `SELECT` column order **must** match the Entity's `__construct`
  positional argument order (PDO::FETCH_FUNC contract via Ray.MediaQuery's
  `FetchNewInstance`). Adding a column means updating both files in
  lock-step.
- `DbQueryInterceptor` routes every `#[DbQuery]` call through
  `getRow` / `getRowList` based on the return type. Writes (`void`
  return) still go through the same path; do **not** call `exec()`
  directly.

## 6. File/data layout

| Kind | Location |
|------|----------|
| Read/Write SQL | `var/db/sql/<entity>_<verb>.sql` |
| Doctrine migrations | `var/db/migrations/Version<timestamp>.php` |
| Response JSON Schema | `var/json_schema/<entity>.json` (flat, no subdirs) |
| Input JSON Schema | `var/json_validate/<entity>_<verb>.json` |
| Fake data | `var/fake/<entity>.json` (deterministic, `mt_srand(42)`) |
| ALPS profile | `var/alps/profile.json` (single source of truth for semantics) |
| Generated apidoc | `docs/index.html`, `docs/openapi.json`, `docs/llms.txt`, `docs/schemas/*` |

## 7. Tests

- Unit tests (no DB): default suite, run by `vendor/bin/phpunit`.
- Integration tests (real DB): run against MySQL (not SQLite). Auto-skip
  when MySQL is unreachable.
- No mocks. External services use Docker; internal dependencies use
  Fake classes from `src/Fake/`.

## 8. Process

| What | Convention |
|------|-----------|
| Commit message | English, multi-paragraph allowed for non-trivial change |
| Commit granularity | One logical phase per commit |
| Failed/exploratory commits | Kept as history, not squashed |
| `Co-Authored-By` line | Not used (Claude-only contribution acknowledged via commit message body if needed) |
| `CLAUDE.md` in repo | Yes — project-specific gotchas for future AI sessions |
| Branch from main (`1.x`) | Always create a feature branch; never commit to `1.x` directly |

---

## See also

- [architecture.md](architecture.md) — BDR pattern, contexts,
  intentionally-not-built list
- [alps.md](alps.md) — ALPS profile flow into code
- [resources.md](resources.md) — HAL response shapes per resource
- [journal/decisions-to-consult.md](journal/decisions-to-consult.md) —
  original discussion log; the "OK" outcomes there are codified above
