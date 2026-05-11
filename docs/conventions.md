# Conventions

[日本語](ja/conventions.md)

Cross-cutting "how to write code in this codebase" rules. Architecture
and pattern explanations live in [architecture.md](architecture.md);
this file is the companion that codifies the *decisions* made during
construction (see [journal/decisions-to-consult.md](journal/decisions-to-consult.md)
for the original discussion log).

When in doubt, follow what's here. New conventions land here first,
then the code/docs follow.

## Contents

1. [Code structure](#1-code-structure) — namespaces, directory layout, Read/Write split
2. [Contexts](#2-contexts) — `hal-api-app` / `cli-` / `fake-` / `test-` composition
3. [Naming](#3-naming) — class, query method, resource property, SQL filename, ALPS, HAL rel
4. [Resource patterns](#4-resource-patterns) — body construction, status codes, after-INSERT id, pagination, **input shape & validation**, exceptions, named arguments, method order
5. [Read/Write SQL contract](#5-readwrite-sql-contract) — column order, fetch mode, write-id detection
6. [File / data layout](#6-filedata-layout) — `var/` artefact placement
7. [Tests](#7-tests) — context wiring, hermetic fakes, hypermedia workflow tests
8. [Process](#8-process) — adopting a convention, retiring a deprecated one

---

## 1. Code structure

| What | Convention |
|------|-----------|
| Namespace root | `MyVendor\Cms` |
| Layer directories | `src/Entity/`, `src/Query/`, `src/Resource/App/`, `src/Module/`, `src/Service/` |
| Fake placement | `tests/Fake/` — `composer.json` maps `MyVendor\Cms\` to both `src/` and `tests/` (autoload + autoload-dev), so `fake-hal-api-app` (dev) and `test-hal-api-app` (test) both resolve `MyVendor\Cms\Fake\*`. Production (`composer install --no-dev`) does not load `tests/`, keeping the prod artefact free of fake bindings |
| Module composition | `FakeModule` provides the binding; `TestModule` *installs* `FakeModule`. Two-stage so prod/cli/fake/test contexts can compose differently |
| Resource placement | `src/Resource/App/<Class>.php` — every URI is a class. No `App/Index.php` unless a "/" entry-point is meaningful |
| Read/Write split | Always two interfaces per entity: `<Entity>QueryInterface` (Read) and `<Entity>CommandInterface` (Write). Both live in `src/Query/` — the interface name suffix carries the Read/Write distinction so `MediaQuerySqlModule` can scan a single directory. Never mix Read and Write methods on the same interface |
| MediaQuery result placement | `src/Result/*` contains typed Ray.MediaQuery result objects returned from `src/Query/*Interface` methods. These are not domain entities; they wrap query execution context or DML metadata. For read queries, treat them as query-local projections: typed read-side views assembled from a specific `#[DbQuery]` result, not controller/service helpers. Keep the directory dedicated to query results so `src/Query` and `src/Result` stay a readable pair |

### Variation resources

`src/Resource/App/Variations/` contains comparison-only resources. The Article
set is fixed at exactly three GET implementations. They are not registered in
the ALPS profile and must not change the canonical
`src/Resource/App/Article.php` path.

| Variation | Axis | Question it answers |
|---|---|---|
| [`Variations\ArticleAsArray`](../src/Resource/App/Variations/ArticleAsArray.php) | data shape (entity vs array) | "Is the entity class worth the ceremony?" |
| [`Variations\ArticleSqlQuery`](../src/Resource/App/Variations/ArticleSqlQuery.php) | abstraction level (declarative `#[DbQuery]` vs programmatic `SqlQuery` class) | "What if `#[DbQuery]` isn't enough?" |
| [`Variations\ArticleRawPdo`](../src/Resource/App/Variations/ArticleRawPdo.php) | framework presence (MediaQuery vs raw `ExtendedPdoInterface`) | "What is MediaQuery actually doing for me?" |

Do not add a fourth Article variation. A non-Article variation is allowed only
when it demonstrates a different framework axis that cannot be shown by the
three Article reads. The current example is
[`Variations\MediaStream`](../src/Resource/App/Variations/MediaStream.php),
which keeps canonical `Media::onGet()` JSON-shaped and demonstrates
`BEAR.Streamer` by assigning an open file handle to `$this->body` with explicit
`Content-Type`, `Content-Length`, and `Content-Disposition` headers. It has no
`#[JsonSchema]` on the success path because the response body is a stream, not a
JSON document.

Use `composer demo:variations` when the goal is to compare these alternatives;
keep `composer demo` as the main golden path. See
[`src/Resource/App/Variations/README.md`](../src/Resource/App/Variations/README.md)
or
[`README.ja.md`](../src/Resource/App/Variations/README.ja.md)
for the short reading guide.

## 2. Contexts

| Context | Where it runs |
|---------|--------------|
| `hal-api-app` | Production HTTP |
| `cli-hal-api-app` | `bin/app.php`, `composer app`, `bin/cli/*` scripts |
| `fake-hal-api-app` | Dev runtime against `FakeSqlQuery` (no DB) — e.g. `composer fake`, manual exploration |
| `test-hal-api-app` | PHPUnit (composes `FakeModule`) |
| `async-hal-api-app` | BEAR.Async `AsyncParallelModule` context; worker threads use `hal-api-app` |
| `async-test-hal-api-app` | Async PHPUnit context; worker threads use `test-hal-api-app` |
| `async-slow-fake-hal-api-app` | Demo-only timing context; worker threads use `slow-fake-hal-api-app` |

`fake-`, `test-`, and `async-` are canonical prefixes. The application injector
handles `async-` as an async overlay on the same context without the leading
prefix; do not change Resource code to make it async.
`slow-` is a demo-only prefix used to add deterministic latency to embedded
resources for timing comparison.
The resulting worker context is passed explicitly to `AsyncModule` to avoid
recursive parallel runtime creation.

## 3. Naming

### Class / interface
- Read interface: `<Entity>QueryInterface` (e.g. `ArticleQueryInterface`)
- Write interface: `<Entity>CommandInterface` (e.g. `ArticleCommandInterface`)
- Entity: `final readonly class` with public properties only

### Query / Command method names
**Reads use noun-form (queryable noun + qualifier); writes use verb-form
(imperative action).** Same vocabulary as the SQL filenames below, so
that `#[DbQuery('article_item')] public function item(int $id)` speaks
one language across attribute and signature.

| Kind | Method shape | Examples |
|---|---|---|
| Single-row read by primary key | `item` | `item(int $id)` |
| Single-row read by natural key | `by<NaturalKey>` | `bySlug`, `byEmail`, `byFilename` |
| Multi-row read | `list` (variants: `list<Variant>`) | `list()`, `listByArticle(int $articleId)` |
| Single-row write | imperative verb | `add`, `update`, `delete` |
| Link-table write | imperative verb | `clear`, `link` (e.g. `ArticleTagCommandInterface`) |

`item` (canonical PK lookup) and `by<NaturalKey>` (alternate access
path) are intentionally distinct shapes: PK is the technical identity
handle, natural keys (`slug`, `email`, `filename`) are domain-meaningful
alternates. The asymmetry encodes that real distinction.

`item` ↔ `list` form a lexical pair that mirrors BEAR's resource
shapes: `Article` (item resource) ↔ `Articles` (collection resource);
`item($id)` ↔ `list(...)`.

After INSERT, fetch the new row by natural key via `by<NaturalKey>`,
not `lastInsertId`. The natural key is what the client supplied;
re-SELECT gives back the assigned id without driver-dependent state.

### Resource property names

Resources hold dependencies on Query/Command interfaces. Reads are
**queryable nouns**, writes are **action tools** — name them
accordingly:

| Dependency | Property pattern | Example |
|---|---|---|
| Primary entity's `<Entity>QueryInterface` | `$<entity>` | `private ArticleQueryInterface $article` |
| Primary entity's `<Entity>CommandInterface` | `$<entity>Cmd` | `private ArticleCommandInterface $articleCmd` |
| Auxiliary / link-entity's interface | `$<entity><Role>` | `private ArticleTagCommandInterface $articleTagCmd` |

The asymmetric naming carries information:

- `$this->article->item($id)` reads as "the article-source's item by
  id" — receiver is a queryable noun, method qualifies the query.
  Mirrors Rails `Article.find(id)` in role even though syntax differs.
- `$this->articleCmd->add(...)` reads as "the article command, add" —
  receiver is a tool, method names the action.

In a Resource focused on a single entity (`Article`, `Author`, etc.),
the unsuffixed property name reserves the read role for the primary
entity, distinguishing it from auxiliary write-only links.

### SQL filenames
- Pattern: `<entity>_<verb>.sql` in `var/db/sql/`
- Verbs match the method names above:
  - `item` ↔ `<entity>_item.sql`
  - `by_<key>` ↔ `<entity>_by_<key>.sql`
  - `list` ↔ `<entity>_list.sql`, `<entity>_list_by_<x>.sql`
  - `add` / `update` / `delete` ↔ same
  - link-table verbs ↔ `<link>_clear.sql`, `<link>_link.sql`
- Examples: `article_item.sql`, `article_by_slug.sql`,
  `article_list.sql`, `article_add.sql`, `article_update.sql`,
  `article_delete.sql`, `article_tag_clear.sql`, `article_tag_link.sql`

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

This split is also enforced from the test side — see
[§7.1 Hypermedia workflow tests](#71-hypermedia-workflow-tests).

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

### Page template not-found pattern

Page resources that load a single primary entity by id return 404 with
`body = ['message' => '<X> not found']`. The Qiq template still gets
invoked on 4xx, so without a guard it warns when reading properties on
the null entity. Throw a per-entity domain exception at the top of the
template; the framework's `catch (Throwable)` path routes to
`templates/Error.php`.

```php
<?php
/**
 * @var \MyVendor\Cms\Entity\Article|null $article
 */
if (! isset($article) || $article === null) {
    throw new \MyVendor\Cms\Exception\ArticleNotFoundException();
}
?>
```

The exception is per-entity (`ArticleNotFoundException`,
`AuthorNotFoundException`, …), not shared, mirroring the existing
`MyVendor\Cms\Exception\*NotFoundException` family. Only the *primary*
entity needs the guard; list-shaped vars are always lists (possibly
empty), not null.

Every Page test for such a resource includes
`testNotFoundRendersErrorTemplate` so the warning regression is caught.

### Status codes
| Method | Success | Not found | Validation fail |
|--------|---------|-----------|-----------------|
| GET | 200 | 404 | n/a |
| POST (creates a resource) | 201 + `Location` header | n/a | 422 (via `#[JsonSchema(params:)]`) |
| POST (action / non-creating) | 200 + body | n/a | 422 (via `#[JsonSchema(params:)]`) |
| PUT | 200 | 404 | 422 |
| DELETE | 204 | 404 | n/a |
| Duplicate `slug` (or other unique key) | — | — | 409 (via DB `UniqueConstraintViolation`, no manual catch) |

**POST is not always creation.** `201 + Location` only applies when the
POST adds a new addressable resource (e.g. `Article::onPost` creates
`/article?id=N`). Action-style POSTs that don't create a new URI —
auth code-for-session exchange, password reset confirm, "log this
event" endpoints — return `200` with the result body and no `Location`
header. The `#[JsonSchema(params:)]` input-validation rule still
applies.

### After-INSERT id
Never `lastInsertId` (driver-dependent, awkward to fake). Always
re-SELECT via `by<NaturalKey>` using the natural key the client
supplied (slug / email / filename). The canonical Resource-facing
`Command` side returns `void`. If a non-Resource caller needs DML
metadata, keep that as an explicit sample/read-model command and return
MediaQuery's `AffectedRows`; see [MediaQuery samples](media-query-samples.md).

### Pagination
Article collection reads use Ray.MediaQuery's `#[Pager]` and return
`PagesInterface`. Resource code reads `$pages[$page]`, maps the returned
Page object's associative `data` rows through `ArticleFactory`, and uses
`total`, `hasNext`, and `maxPerPage` fields. The DB-free fake implements
the same contract with Pagerfanta's `ArrayAdapter`, so tests exercise the
same pagination shape without requiring PDO-backed pages.

### Input shape & validation

The codebase deliberately mixes two input shapes — DTO at the Resource
boundary for some endpoints, named scalar parameters for others —
chosen per endpoint by **what the actual signature shape calls for**,
not by uniform rule. The pattern catalog is short on purpose; the
educational value is in seeing each pattern *applied where it fits*.

#### Current applications and rationale

| Endpoint | Shape | Validation | Rationale |
|---|---|---|---|
| `Article::onPost` | `ArticleCreateInput` DTO | `#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]` | 9 fields including `tagIds` list — flat signature would be unreadable; cohere as a struct |
| `Article::onPut`  | `ArticleUpdateInput` DTO | `#[JsonSchema(schema: 'write_response.json', params: 'article_update.json')]` | 7 fields including tri-state `tagIds` (`null`/`[]`/list with replace semantics) — tri-state needs typed carrier |
| `Auth::onPost`    | `AuthExchangeInput` DTO  | `#[JsonSchema(schema: 'auth_response.json', params: 'auth_exchange.json')]` | OAuth `code`/`state` is a meaningful struct, not two unrelated scalars; readability over field count |
| `Author::onPost`  | scalar | `#[JsonSchema(params: 'author_create.json')]` | 3 trivial fields; method signature *is* the contract |
| `Author::onPut`   | scalar | `#[JsonSchema(params: 'author_update.json')]` | same |
| `Tag::onPost`     | scalar | `#[JsonSchema(params: 'tag_create.json')]`    | 2 fields |
| `Category::onPost`/`onPut` | scalar | `#[JsonSchema(params: 'category_*.json')]` | 4 fields, all independent scalars |
| `Media::onPost`   | scalar | `#[JsonSchema(params: 'media_create.json')]`  | 6 fields but each is an independent property; no nesting or tri-state — borderline DTO territory, intentionally scalar to show the upper bound of "still readable as a flat list" |

**DTO-shaped methods are validated end-to-end** as of
[BEAR.Resource 1.31.1](https://github.com/bearsunday/BEAR.Resource/releases/tag/1.31.1)
([#356](https://github.com/bearsunday/BEAR.Resource/issues/356)) and
[BEAR.ApiDoc 1.9.1](https://github.com/bearsunday/BEAR.ApiDoc/releases/tag/1.9.1)
([#81](https://github.com/bearsunday/BEAR.ApiDoc/issues/81)).
`JsonSchemaInterceptor` now unpacks `#[Input]` DTO arguments before
validating against the `params:` schema, so `var/json_validate/<entity>_<verb>.json`
constraints (slug regex, status enum, length limits, …) are enforced
at the resource boundary. `OpenApiGenerator` emits the matching
`requestBody` schema, so the openapi contract reflects the same
shape. See
[`docs/journal/decisions-to-consult.md`](journal/decisions-to-consult.md)
P8-#45 for the diagnosis history.

#### Pitfall: typed-array DTO fields and the validation order

Validation runs *after* DTO hydration, so a malformed value for a
typed property (e.g. a scalar `tagIds=1` against `public array
$tagIds`) reaches the constructor first and raises `TypeError` →
5xx, never reaching `JsonSchemaInterceptor`. Until BEAR.Resource
moves params validation in front of hydration, defend the typed
fields inside the DTO: declare the parameter `mixed`, type-check it
explicitly, and throw `BEAR\Resource\Exception\ParameterException`
(maps to 400) for bad shapes. `ArticleCreateInput::tagIds` and
`ArticleUpdateInput::tagIds` follow this pattern. Keep the runtime
check minimal — `is_array` only — and let the JSON Schema's
`items` / `minimum` keep doing the per-element validation it
already does. Note that `mixed` always allows null in
`Ray\InputQuery`'s default-value resolution: an omitted `tagIds`
arrives as `null`, not as the constructor's declared default, so
coalesce `null` to your intended default (`[]` for create-style,
`null` for tri-state update) before the `is_array` gate.

`Auth::onPost` uses a dedicated `auth_response.json` (string subject
id from the OAuth provider) rather than the shared
`write_response.json` (integer DB id) — pick the response schema by
what the endpoint actually returns, not by template.

#### Decision rule (fit-driven)

When designing a new endpoint, decide by the *shape's* needs, not by
seeking pattern coverage:

- **Stay scalar** when the parameter list is a short, flat list of
  trivial fields whose names map 1:1 to JSON Schema properties, with
  no nested or tri-state structure. `#[JsonSchema(params: '<entity>_<verb>.json')]`
  validates the named arguments and the method signature *is* the
  contract. This is the default.
- **Use an Input DTO** when any of:
  - parameter count crosses the readability threshold (~7+ fields)
  - any field has tri-state or partial-update semantics (e.g.
    `null` / `[]` / non-empty list, where omitted ≠ explicit empty)
  - the fields cohere as a named struct that's meaningful beyond
    the resource (e.g. an OAuth callback pair)

  Define `MyVendor\Cms\Input\<Action>Input` as `final readonly class`
  with `#[Input]` on each constructor parameter, type the resource
  argument as `#[Input] <Dto>`, and BEAR.Resource's `InputParam` (via
  `Ray\InputQuery\InputQueryInterface`) materialises the object from
  the flat request array before the method runs. No module install —
  bound by `BEAR\Resource\Module\ResourceClientModule`.

Examples in this codebase: `src/Input/ArticleCreateInput.php`,
`ArticleUpdateInput.php`, `AuthExchangeInput.php`, consumed by
`Article::onPost`, `Article::onPut`, `Auth::onPost`.

#### Page resources stay scalar (provisional)

Even when a Page resource crosses the 7-field threshold or has
tri-state form input (e.g. `Page/Admin/Article::onPost`), keep the
parameter list scalar for now. A DTO is technically the better fit
for form receivers — it can host derived fields (`birthdate → age`)
and acts as a typed `unsafe → safe` boundary that absorbs HTML form
shape (`""` → `null`, mixed → `list<int>`, …). The blocker is
documentation: ApiDoc does not yet consume the phpdoc input-param
expansion (see `docs/001-input-param-expansion.md`), so a Page Input
DTO would not surface in the documentation today.

Treat ApiDoc gaining input-param support as the migration trigger.
When that lands, Page resources move to `#[Input] <Dto>` form input,
and the form-normalisation logic that currently lives in resource
helpers (e.g. `Page/Admin/Article::normaliseValues`) moves into the
DTO constructor.

App-layer Input DTOs (`ArticleCreateInput`, etc.) are unaffected —
those are already the validated/documented surface that Page
resources POST into via `app://self/<resource>`.

#### Why DTOs are not pushed through the Command interface

Ray.MediaQuery natively supports Input DTOs in `#[DbQuery]` interfaces
(verified via `vendor/ray/media-query/src/ParamConverter.php::expandInputObjects()`;
documented in the official manual:
https://bearsunday.github.io/manuals/1.0/ja/database_media.html#rayinputqueryとの連携).
We deliberately do **not** use it.

Reason — the Resource layer here is not a passthrough.
`Article::onPost` / `onPut` unpacks the DTO into named scalar args at
the Command boundary because the Resource also runs `syncTags()`,
performs a `bySlug` round-trip, and may rearrange write/read
sequencing. Hiding that work behind a single DTO pass would
misrepresent what the Resource does. The unpack step (~8 lines) reads
as documentation of which fields hit SQL versus which fields drive
separate orchestration (e.g. `tagIds` → `syncTags`, never bound into
`article_update.sql`).

This is the per-codebase reason to keep the Command boundary scalar;
Ray.MediaQuery's DTO support remains the right choice for codebases
where the Resource-to-Command boundary is genuinely a passthrough.

Coupling `<Entity>CommandInterface` to a per-resource `Input` shape
would also erase the §1 Read/Write layer split. Positional unpacking
at the call site is consistent with the §4 named-arguments rule
below — clear verb-then-fields order, no literal bool, no skipped
middle.

### Exceptions
- No generic `LogicException` / `RuntimeException`. Define
  `MyVendor\Cms\Exception\<DomainName>Exception` for any thrown
  exception originating in `src/`.
- Read errors (not found) return 404 via `$this->code` — do not throw.

### Named arguments at call sites
**Positional is the default.** Use named arguments only where
positional breaks the reader's ability to decode the call. The PHP
8.0 RFC introduced named arguments for exactly two situations; we
adopt those two, and nothing more.

Use named when:

1. **A literal `true` / `false` is passed.** `execute($sql, true,
   false)` cannot be decoded from type or order; the signature has to
   be opened. This is the RFC's flagship example (Popov: "three
   booleans").
   ```php
   // bad
   $query->execute($sql, true, false);
   // good
   $query->execute($sql, cache: true, strict: false);
   ```
   Bool passed via a *variable* (`$query->execute($sql, $useCache)`)
   carries meaning in the variable name; positional is fine.

2. **A middle optional argument is skipped.** Filling defaults just
   to reach the one you wanted erases the call's intent.
   ```php
   // bad
   htmlspecialchars($s, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
   // good
   htmlspecialchars($s, double_encode: false);
   ```

Stay positional otherwise — even for many-arg calls — when type and
verb order make the call decodable:

```php
new Point($x, $y);
new Range($min, $max);
$fs->move($src, $dst);                    // direction-verb
$cache->remember($key, $ttl, $callback);
$client->request($method, $url, $options);
$command->add($slug, $title, $body, $excerpt, $status,
              $publishedAt, $authorId, $categoryId);
```

Argument count alone is **not** a reason to use named arguments. A
call that's hard to read because there are too many arguments is a
*signature-design* problem, not a call-site problem; fix the design.

Decision order at the call site:
1. Literal `true`/`false` → named
2. Skipping a middle optional → named
3. Otherwise → positional

When named keeps creeping in, the signature is the smell:
- **Aggregate into a value object.** Many-arg commands take one DTO.
  Constructors of "struct objects" (Larry Garfield's term) are the
  natural place for named.
- **Drop `bool` parameters.** Split `save()` / `forceSave()`, or use
  `enum SaveMode`. PHPMD `BooleanArgumentFlag` flags this for the
  same reason.
- **Split the method.** "`true` / `false` switches behavior" is an
  SRP violation in disguise.

Scope: this convention covers internal interfaces (Read/Write
boundaries, Resource-layer calls). Public library APIs are
out-of-scope — there, parameter names become part of the BC
contract; consider `@no-named-arguments` (PHPStan / Psalm /
PHP-CS-Fixer) instead.

Explicitly **not** adopted:
- "3+ arguments → named" — pulls in healthy calls like
  `cache->remember($key, $ttl, $callback)` and erodes positional as
  the default.
- "5+ arguments → named" — count thresholds hide design problems
  behind call-site syntax.
- "Same-typed 2+ → named" — sweeps in `Point(x, y)` and
  direction-verbs `move(src, dst)`.
- "Any nullable parameter → named" — `?string` itself doesn't cause
  swap bugs; the *skip-the-default* case is already covered by rule 2.
- "Any `bool` parameter → named" — `$force` via a variable is
  self-explaining. The breakage is specifically literal `true`/`false`.

References:
- PHP 8.0 named arguments RFC (Popov)
- PHP Internals News Ep. 59 — Popov frames `true, true, false` as
  the canonical case
- Larry Garfield, "PHP 8.0 named arguments" — named as a *targeted*
  tool for struct-object construction
- PHPMD `BooleanArgumentFlag` — `bool` parameter as SRP smell
- `@no-named-arguments` in PHPStan / Psalm / PHP-CS-Fixer — for
  library-boundary BC, not internal style

### Method order inside a Resource
1. `__construct`
2. Public `on*` handlers in HTTP-verb order (`onGet`, `onPost`, `onPut`,
   `onDelete`)
3. `private` helpers, after every public method

Reading top-to-bottom should mirror the public surface first, the
implementation detail last. Helpers above the handlers force the reader
to skim past internal plumbing before reaching the entry point.

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
| README spec | `docs/readme-spec.md` (shared top-level README shape) |

## 7. Tests

- Unit tests (no DB): default suite, run by `vendor/bin/phpunit`.
- Integration tests (real DB): run against MySQL (not SQLite). Auto-skip
  when MySQL is unreachable.
- No mocks. External services use Docker; internal dependencies use
  Fake classes from `tests/Fake/`.

### 7.1 Hypermedia workflow tests

A workflow test in `tests/Hypermedia/` is a **user story told by
linking small steps with `#[Depends]`** — the `ResourceObject`
returned by one step is the input the next step follows a rel from.
**One file per story**: the class name is the story title, the
method names are the steps, and PHPUnit's testdox output reads top
to bottom as the user story:

```text
Reader Browses By Tag (MyVendor\Cms\Hypermedia\ReaderBrowsesByTag)
 ✔ Opens tag list
 ✔ Picks a tag
 ✔ Views articles under that tag
 ✔ Opens an article
 ✔ Looks up the author

Editor Manages Article (MyVendor\Cms\Hypermedia\EditorManagesArticle)
 ✔ Creates an article
 ✔ Reads back the new article
 ✔ Revises the article
 ✔ Retires the article
```

Each step is one line in the body
(`return $this->follow($prev, $rel, $vars)`); the narrative is in
the class name, the method names, and the `#[Depends]` chain — not
in the body. Workflow tests are different in purpose from the
per-resource smoke tests in `tests/Resource/`: those validate one
endpoint at a time; workflow tests validate that the resources are
*connected* the way ALPS says they are.

Rules:

1. **One file per story.** Each story is its own
   `<Actor><Verb>Test` class extending
   `Hypermedia\AbstractWorkflowTestCase`. The class name carries
   the actor (`ReaderBrowsesByTag`, `EditorManagesArticle`), so
   step methods drop it (`testOpensTagList`, not
   `testReaderOpensTagList`). Contract pins (e.g. HAL envelope
   shape) live in their own `*ContractTest` class, separate from
   the stories.
2. **Only one hard-coded URI per story — the entry point.** Every
   subsequent transition goes through `ResourceInterface::href($rel,
   $vars, $ro)`, which reads the `#[Link]` annotation off the source
   resource and expands the URI Template. Renaming a rel — i.e.
   renaming an ALPS Choreography transition — will break the chain
   and surface here.
3. **One step per `#[Depends]`-linked test method.** The first test
   in a story performs the entry GET (or POST) and returns the
   `ResourceObject`; each follow-up declares
   `#[Depends('previousStep')]` and receives that object as its
   first parameter. Method names are third-person narrative present
   so the testdox report reads like the user story.
4. **Pass the specific id, do not rely on body-merge expansion.**
   `Anchor::href()` automatically merges the source body into the
   URI Template's variables, so `follow($ro, 'goAuthor')` would
   *appear* to work. It does not, because every resource exposes its
   own primary key as `id` (a deliberate, project-wide convention),
   and Link templates also use `{?id}`. Body-merge silently feeds
   the source's `id` into a foreign-key slot — for example, an
   article's id ends up requesting an author with the same numeric
   value, which usually returns a 200 for the wrong author. Always
   pass the specific id explicitly:
   `follow($article, 'goAuthor', ['id' => $article->body['authorId']])`.
   This keeps the cross-entity flow (article's `authorId` → author's
   `id`) visible at the call site instead of buried in a template.
5. **Per-step shape validation belongs to `#[JsonSchema]`, not
   workflow tests.** Workflow tests assert status codes, rel
   chains, and business invariants (e.g. an edit must be visible to
   the next read). They do not duplicate field-level checks. The
   `follow()` helper in `AbstractWorkflowTestCase` centralises the
   "transition succeeded" check so step bodies stay one-liners.
6. **Canonical lifecycle: create → read → edit → read → delete →
   404.** This is the minimum coverage for any write-capable
   resource and is the spine of `EditorManagesArticleTest`
   (`testCreatesAnArticle` → … → `testRetiresTheArticle`).
7. **`_embedded` vs `_links` are pinned in a contract test, not in
   stories.** Taxonomy nouns (`author`, `category`, `tagList`)
   appear under `_embedded`; Choreography verbs (`goAuthor`,
   `doCreateArticle`) appear under `_links`. The HAL envelope
   contract is asserted in `HalEnvelopeContractTest` so a slip on
   either side fails one isolated test instead of polluting a
   narrative (see
   [§3 HAL rel naming](#hal-rel-naming--split-by-alps-layer)).
8. **`Location` after `POST` is the navigation cue.** A hypermedia
   client cannot guess the URL of a just-created resource, so
   `onPost` returns `Location: /<noun>?id=<id>` and the workflow
   test follows it the same way a browser would. PUT and DELETE are
   unsafe transitions invoked directly by HTTP method — they are
   not advertised as `_links` rels by design.

A test that hard-codes `app://self/article` mid-chain, asserts
JsonSchema-shaped fields, or compresses an entire story into one
method body is a workflow test in name only. Move such checks to
the appropriate `tests/Resource/` test, rely on the schema
attribute, or split the narrative into `#[Depends]`-linked steps in
its own `<Actor><Verb>Test` file.

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
- [readme-spec.md](readme-spec.md) — shared README shape for
  BEAR.Sunday-aligned reference projects
- [resources.md](resources.md) — HAL response shapes per resource
- [journal/decisions-to-consult.md](journal/decisions-to-consult.md) —
  original discussion log; the "OK" outcomes there are codified above
