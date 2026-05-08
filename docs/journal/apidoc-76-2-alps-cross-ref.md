# ApiDoc#76 — sub-proposal 2/3: ALPS cross-reference

> Sub-proposal #2 of [bearsunday/BEAR.ApiDoc#76](https://github.com/bearsunday/BEAR.ApiDoc/issues/76)
> "Pull data from semantic-ex artefacts (ALPS, JSON Schema, fakes) into generated API docs".
> Sub-proposal #1 (inline JSON Schema constraints / examples) shipped in BEAR.ApiDoc 1.9.x.
> Sub-proposal #3 (fake JSON examples) is filed separately.

## Motivation

ApiDoc already loads `var/alps/profile.json` and exposes a `semanticDictionary`,
but the cross-reference is shallow: it only reads *state* descriptors, only
fires when the user manually annotates a method with `#[Alps(id: ...)]`, and
the semantic info reaches the rendered docs as a single inline string.
Meanwhile every BEAR.Sunday resource already declares its semantics through
`#[Link]` (Choreography verbs — `goArticle`, `doCreateArticle`) and
`#[Embed]` (Taxonomy nouns — `author`, `category`, `tagList`); JSON Schema
property names line up 1:1 with ALPS descriptor ids by convention. We
should resolve those automatically and turn each rel and each property name
into a hyperlink into `alps.html`, so a reader on a method page can pivot
to the descriptor's definition, doc text, schema.org `def` URI, and
related transitions in one click — across HTML, Markdown, and OpenAPI.

## Concrete output examples (before / after)

Reference resource — [`src/Resource/App/Article.php`](https://github.com/koriym/MyVendor.Cms/blob/1.x/src/Resource/App/Article.php):

```php
#[Link(rel: 'goArticleList', href: 'app://self/articles')]
#[Link(rel: 'goAuthor',      href: 'app://self/author{?id}')]
#[Embed(rel: 'author',       src: 'app://self/author')]
#[Embed(rel: 'category',     src: 'app://self/category')]
public function onGet(int $id): static
```

Reference profile — [`var/alps/profile.json`](https://github.com/koriym/MyVendor.Cms/blob/1.x/var/alps/profile.json):

```json
{"id": "goArticleList", "type": "safe", "rt": "#ArticleList",
 "title": "View Article List",
 "doc": {"value": "List published articles, filterable by category, tag or status."}}
{"id": "title", "title": "Title", "def": "https://schema.org/headline"}
```

### Markdown — `### Links`

Before (current `DocMethod::getLinks()`):

```markdown
#### Links

| Relation     | URL                          |
|--------------|------------------------------|
| goArticleList| /articles.md                 |
| goAuthor     | /author.md{?id}              |
```

After:

```markdown
#### Links

| Relation                                      | ALPS type | URL              | Description                                    |
|-----------------------------------------------|-----------|------------------|------------------------------------------------|
| [goArticleList](alps.html#goArticleList)      | safe      | /articles.md     | View Article List — List published articles…  |
| [goAuthor](alps.html#goAuthor)                | safe      | /author.md{?id}  | View Author                                    |
```

### Markdown — response property table

Before:

```markdown
| Name  | Type    | Description       | Required |
|-------|---------|-------------------|----------|
| title | string  | Article headline  | yes      |
```

After (descriptor name links into `alps.html`; `def` URI surfaced as
"defined as" when present):

```markdown
| Name                                  | Type    | Description                              | Required |
|---------------------------------------|---------|------------------------------------------|----------|
| [title](alps.html#title)              | string  | Article headline (defined as schema.org/headline) | yes      |
```

### HTML

Same data, rendered as anchors inside the existing template — `<a
href="alps.html#goArticleList" title="View Article List — List published
articles…">goArticleList</a>` — so the existing `alps.html` page (already
emitted via `HtmlGenerator`) becomes the canonical landing target rather
than dead text.

### OpenAPI

Add an `x-alps` operation extension and an `externalDocs` block keyed off
the rel:

```yaml
paths:
  /article:
    get:
      operationId: goArticle
      x-alps:
        descriptor: goArticle
        type: safe
        rt: '#Article'
      externalDocs:
        url: alps.html#goArticle
        description: ALPS descriptor
      responses:
        '200':
          content:
            application/hal+json:
              schema:
                properties:
                  title:
                    type: string
                    description: Article headline
                    x-alps-descriptor: title
                    externalDocs:
                      url: alps.html#title
                      description: 'schema.org/headline'
```

`operationId` already comes from `#[Alps]` when present (see
`OpenApiGenerator.php:147-151`); falling back to the matched
`#[Link]`/`#[Embed]` rel removes the manual annotation.

## Implementation sketch

All work stays inside `bear/api-doc`; no schema or runtime changes elsewhere.

1. **Promote the dictionary to a typed index** —
   `ApiDoc::registerAlpsProfile()` (`ApiDoc.php:194-211`) currently keeps
   only `SemanticDescriptor` entries with their title. Replace `ArrayObject<string,
   string>` with a small `AlpsIndex` value object that retains both
   `SemanticDescriptor` and transition descriptors (`safe`/`unsafe`/`idempotent`),
   exposing `lookup(string $id): ?AlpsRef` where `AlpsRef` carries
   `{id, kind, title, doc, def, rt, type}`.

2. **Default the profile path, with explicit error policy** —
   `Config::$alps` is empty unless declared in `apidoc.xml`
   (`Config.php:55,100-103`). Default to
   `<projectDir>/var/alps/profile.json` when the file exists, so
   semantic-ex projects get cross-references without configuration.
   Error policy is split by how the path arrived:
   - **Auto-discovered (default path)** — fail-open. File absent →
     silent skip (current behaviour). File exists but unreadable or
     malformed JSON → log one warning to STDERR including path and
     parser message, leave `Config::$alps` empty, continue
     doc-generation. A broken default profile must never break the
     build.
   - **Explicitly configured** (`<alps>` element in `apidoc.xml`) —
     fail-fast. Missing file keeps the current
     `AlpsFileNotFoundException`; unreadable / malformed JSON throws a
     new `AlpsProfileParseException` carrying path + parser message.
     The user opted in, so a silent fallback would hide the bug.

   `ApiDoc::registerAlpsProfile()` (`ApiDoc.php:194-211`) is the single
   point where both branches converge, so the catch/log goes there.

3. **Auto-resolve `#[Link]` / `#[Embed]` rels** — extend
   `DocMethod::getLinks()` / `getEmbeds()` (`DocMethod.php:184-232`) to
   call `AlpsIndex::lookup($rel)` for each attribute and append "ALPS
   type" + "Description" columns. When a match exists, render the rel as
   a Markdown / HTML anchor to `alps.html#<id>`.

4. **Per-property anchors** — `Schema::getDescription()`
   (`Schema.php:160-167`) already falls back to the dictionary; extend
   `SchemaProp` so the property *name* cell can carry an `alps.html#<id>`
   anchor when the index has a descriptor of that id, and append "defined
   as `<def>`" to the description when the descriptor has a `def` URI.

5. **Method-level fallback for `#[Alps]`** —
   `DocMethod::getAlpsSection()` (`DocMethod.php:255-272`) only fires on
   explicit `#[Alps(id:)]`. When that attribute is absent, derive the id
   via this fixed precedence (first match wins, lower matches still
   render in their own Embeds/Links table but do **not** populate the
   method-level ALPS section):

   1. Explicit `#[Alps(id:)]` — always wins (current behaviour
      preserved). Multiple `#[Alps]` attributes on one method keep
      today's "comma-joined ids" rendering.
   2. First `#[Link]` (in source declaration order, as returned by
      `ReflectionMethod::getAttributes()`) whose rel resolves in the
      `AlpsIndex`. Choreography is the canonical method-level mapping
      (e.g. `goArticle` for `Article::onGet`, `doCreateArticle` for
      `onPost`).
   3. First `#[Embed]` (declaration order) whose rel resolves in the
      index. Used only when the method declares no resolvable `#[Link]`
      — uncommon, but covers methods that compose state without
      announcing a transition.
   4. No match → no ALPS section emitted (current behaviour when
      `#[Alps]` is absent).

   The same resolved id flows to `OpenApiGenerator::operationId`
   (`OpenApiGenerator.php:147-151`) and to the `externalDocs` anchor,
   so Markdown / HTML / OpenAPI all agree on which descriptor "owns"
   the method.

6. **OpenAPI surface** — in `OpenApiGenerator.php`:
   - emit `x-alps` on each operation (`{descriptor, type, rt}`) using the
     resolved id (existing `#[Alps]` path at `:147-151` plus the
     auto-resolved rel),
   - emit `externalDocs.url = alps.html#<id>` on operations and on
     individual schema properties whose name resolves in the index,
   - copy `def` to property-level `externalDocs` when present.

7. **HTML template** — `HtmlRenderer.php` already knows
   `$alpsHtmlPath = 'alps.html'` (`HtmlRenderer.php:41,59`); reuse it as
   the anchor base so the existing ALPS page becomes the landing target.

## Test plan

- **Fixtures** — copy a trimmed `profile.json` from MyVendor.Cms into
  `tests/Fake/var/alps/profile.json` (or extend the existing ApiDoc test
  fixtures) covering: state descriptor with `def`, state descriptor
  without `def`, transition with `doc`, transition without `doc`, rel
  with no matching descriptor.
- **Unit** — new `AlpsIndexTest` covering profile loading, transition
  inclusion, and missing-id behaviour.
- **DocMethod / DocClass** — golden-file snapshots for Markdown output
  before/after, asserting:
  - matched rels render as anchors with title/doc;
  - unmatched rels still render (no regression vs current behaviour);
  - `def` URI appears in the property description;
  - `#[Alps]` override still wins when both rel-resolution and explicit
    attribute exist.
- **Rel-resolution precedence** — dedicated tests for the order in step 5:
  - method with only `#[Alps]` → uses the explicit id;
  - method with `#[Alps]` + resolvable `#[Link]` → `#[Alps]` wins;
  - method with multiple resolvable `#[Link]`s → first declared wins,
    others stay in the Links table only;
  - method with only resolvable `#[Embed]` (no Link) → Embed id is used;
  - method with both resolvable `#[Link]` and `#[Embed]` → Link wins;
  - method with no resolvable rels and no `#[Alps]` → no ALPS section
    (parity with today).
- **Profile-load error policy** — auto-discovered path:
  - file absent → silent skip, output identical to no-profile path;
  - file present but malformed JSON → one STDERR warning containing
    path + parser message, generation completes, output identical to
    no-profile path.

  Explicitly configured path:
  - missing file → `AlpsFileNotFoundException` (regression guard for
    today's behaviour);
  - malformed JSON → new `AlpsProfileParseException` with path +
    parser message.
- **OpenApiGenerator** — assert `x-alps` and `externalDocs` present on
  operations and property schemas; assert generated YAML still validates
  against the OpenAPI 3.1 schema.
- **No-profile path** — when `var/alps/profile.json` is absent, output
  must be byte-identical to today's (regression guard for projects that
  do not use ALPS).
- **Reference end-to-end** — run `vendor/bin/apidoc` against the
  MyVendor.Cms reference repo and visually verify that
  `apidoc/article.md`, `apidoc/article.html`, and `apidoc/openapi.json`
  all surface the ALPS context for `goArticle`, `goAuthor`,
  `doCreateArticle`, the `author`/`category`/`tagList` embeds, and the
  `title`/`slug`/`status` properties.

## Out of scope

- Generating ALPS profiles from code (this proposal *consumes* an existing
  profile; generation is what the `bear-to-alps` skill does).
- Changing the ALPS profile format or the `alps.html` renderer; we treat
  both as fixed inputs.
- Sub-proposal #3 (fake JSON examples in response bodies) — handled in a
  separate issue draft.
