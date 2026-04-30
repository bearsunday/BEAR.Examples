# `@input-param` docblock expansion for `#[Input]` DTOs

**Repo:** `bearsunday/coding-standard` (v0.2)
**Depends on:** v0.1 release of `bearsunday/coding-standard` (custom sniffs scaffolding in place)
**Coordinates with:** `bearsunday/BEAR.ApiDoc#76` (reads same `#[Input]` DTOs for openapi)
**Priority:** High — addresses the readability gap that DTO-typed inputs introduce

## Background

BEAR.Sunday lets a Resource method accept a domain DTO via `#[Input]`:

```php
public function onPost(#[Input] ArticleCreateInput $input): static
```

Type-wise this is excellent — the DTO carries domain meaning, validation can be
delegated, and the Resource boundary is explicit. But **the individual fields
the endpoint actually accepts are invisible at the call site**. A reader on
GitHub or in code review must navigate to `ArticleCreateInput.php` to learn
what the endpoint takes.

This problem is real:

- GitHub source view (no IDE) — DTO shape is one click away
- Code review — reviewer must context-switch to the DTO file
- Onboarding — new contributors lose orientation
- Generated API docs — currently the DTO type alone is the contract

## Goal

Allow the **DTO type** (domain meaning) and the **flat field list** (concrete
input contract) to coexist visibly at the method's docblock level:

```php
/**
 * @input-param string $title       Article title (1-255 chars)
 * @input-param string $slug        URL slug (lowercase + hyphens)
 * @input-param string $body        Markdown body
 * @input-param ?string $excerpt    Optional summary
 * @input-param string $status      'draft' | 'published'
 * @input-param ?string $publishedAt ISO-8601, required when status=published
 * @input-param int $authorId
 * @input-param int $categoryId
 * @input-param list<int> $tagIds
 */
public function onPost(#[Input] ArticleCreateInput $input): static
```

The DTO remains the **single source of truth**; the `@input-param` lines are
**derived** (auto-generated, drift-detected, never hand-maintained for long).

## Specification

### Tag

- Name: `@input-param` (parallel to `@param` but specifically for `#[Input]` DTOs)
- Format: `@input-param <type> $<name> [description]`
- One line per DTO constructor parameter
- Order matches the DTO's `__construct` signature (positional)

### Source of truth: the DTO's `__construct`

```php
final readonly class ArticleCreateInput
{
    public function __construct(
        /** Article title (1-255 chars) */
        public string $title,

        /** URL slug (lowercase + hyphens) */
        public string $slug,

        public string $body,
        // ...
    ) {}
}
```

→ Generator reads:
- Parameter name → `$name`
- Parameter type (including nullability, generics from phpstan/psalm hints) → `<type>`
- Parameter property docblock (`/** ... */`) → `description`

### Sniff: `BearSunday.Resources.InputParamSync`

- **Trigger:** any method whose signature contains `#[Input] X $y` (X being a class)
- **Check:** the method's docblock contains exactly the right `@input-param` lines for X's `__construct` parameters (name, type, order)
- **Severity:** WARNING (not ERROR — drift is recoverable, and partial docblocks are still useful)
- **Auto-fixable:** yes — `phpcbf` regenerates the `@input-param` block from the DTO

### CLI generator: `bin/bear-input-params`

For bulk regeneration outside CI:

```bash
vendor/bin/bear-input-params src/Resource/        # check
vendor/bin/bear-input-params --fix src/Resource/  # rewrite
```

Same logic as the sniff's auto-fix, but standalone (useful when adding
`@input-param` to a project that didn't have them before).

### Edge cases

- DTO with **nested DTO field** (e.g. `public Address $address`): emit
  `@input-param Address $address` and stop recursing. Flattening is opt-in
  (separate issue if desired).
- DTO with **union or generic types**: copy the type as-written from the DTO's
  signature; don't try to simplify.
- DTO field with **no docblock**: omit the description column (just `<type> $<name>`).
- Method without docblock: create one with only the `@input-param` block.
- Method with existing docblock and other tags (`@throws`, `@see`): preserve
  them; insert the `@input-param` block before `@throws` and after summary
  description.

### Non-goals

- Generating openapi schema (that's BEAR.ApiDoc's job — see issue #2)
- Validating runtime input (that's `#[JsonSchema]` / DTO type itself)
- Cross-method type aliases (out of scope; see phpstan `@phpstan-import-type`)

## Acceptance criteria

1. **Sniff works on MyVendor.Cms reference**: running on
   `Article::onPost`, `Article::onPut`, `Auth::onPost` correctly identifies
   that no `@input-param` block exists and proposes the right lines.
2. **Auto-fixer round-trips**: applying `phpcbf` and then `phpcs` reports
   zero violations. Re-running `phpcbf` is a no-op.
3. **Fixture tests** in `BearSunday/Tests/Resources/InputParamSyncTest.php`
   cover:
   - missing block
   - partial block (some fields, not all)
   - drifted block (field renamed in DTO)
   - perfect block (no-op)
   - DTO without property docblocks
   - method with non-`#[Input]` parameters mixed in
4. **CLI tool** `bin/bear-input-params` is symlinked into `vendor/bin/` via
   `composer.json` `bin` field; `--help` works; `--fix` performs the same
   rewrite as `phpcbf`.
5. **Docs**: README of `bearsunday/coding-standard` gets a "## `@input-param`"
   section explaining the rationale (this issue's Goal section, condensed)
   and showing before/after.
6. **No regression**: existing v0.1 sniffs still pass on MyVendor.Cms.

## Out of scope

- Issue #2 — BEAR.ApiDoc reading `@input-param` for openapi description
- Issue #3 — MyVendor.Cms migration to `bearsunday/coding-standard`
- Nested DTO field flattening (separate issue if needed)
- Property-level validation tags (e.g. `@input-param string $title <min:1,max:255>`)
- IDE plugins / language server integration

## Implementation notes for the implementer

- The sniff lives at `BearSunday/Sniffs/Resources/InputParamSyncSniff.php`,
  alongside the v0.1 sniffs.
- Use PHPCS's `T_ATTRIBUTE` token to find `#[Input]`. Then use reflection on
  the parameter's type to introspect the DTO. Reflection requires class
  autoload to work — in PHPCS this means the project's autoloader must be
  loaded. See how `slevomat/coding-standard` handles this (e.g.
  `ReferencedClassNamesTrait`).
- For docblock parsing/rewriting, use `phpdocumentor/reflection-docblock` or
  hand-roll a tag matcher. Hand-rolled is probably enough — `@input-param`
  has a stable shape.
- Keep the implementation pure-PHP. Do NOT shell out to phpstan or other
  tooling.

## References

- BEAR.Sunday Input pattern: `Ray\InputQuery\InputQueryInterface`
- MyVendor.Cms reference DTOs: `src/Input/{ArticleCreateInput,ArticleUpdateInput,AuthExchangeInput}.php`
- MyVendor.Cms reference Resources: `src/Resource/App/{Article,Auth}.php`
- Related upstream: `bearsunday/BEAR.ApiDoc#76` (DTO → openapi schema)
- Convention rationale: `MyVendor.Cms/docs/conventions.md` §4 "Input shape & validation"
