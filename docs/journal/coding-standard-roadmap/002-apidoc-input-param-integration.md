# BEAR.ApiDoc reads `@input-param` for OpenAPI description

**Repo:** `bearsunday/BEAR.ApiDoc` (extension to existing #76 work)
**Depends on:**
- `bearsunday/coding-standard` v0.2 (Issue #1) — `@input-param` tag established
- `bearsunday/BEAR.ApiDoc#76` baseline — `#[Input]` DTO → openapi schema generation
**Priority:** Medium — quality-of-life improvement on top of #76
**Coordinates with:** the codex agent currently working on #76

## Background

Issue #1 (in `bearsunday/coding-standard`) introduces `@input-param` docblock
tags that expose the flat input contract on Resource methods using
`#[Input] Dto $input`. Each `@input-param` line carries:

- Type
- Field name
- **Free-text description** (carried over from the DTO's property docblock)

`bearsunday/BEAR.ApiDoc#76` (the in-flight codex task) generates the openapi
`requestBody.content.application/json.schema` from `#[Input]` DTO
introspection — that gives us **types and required-ness** of fields, but
**not human-readable descriptions** unless we add them somewhere.

This issue closes that loop: BEAR.ApiDoc reads `@input-param` lines on the
method and uses their description text to populate the `description` field of
each property in the generated openapi schema.

## Goal

When a Resource method has both an `#[Input]` DTO and a docblock with
`@input-param` lines, BEAR.ApiDoc generates an openapi schema where each
property's `description` field contains the text from the matching
`@input-param` line.

### Before

```yaml
requestBody:
  content:
    application/json:
      schema:
        type: object
        properties:
          title:
            type: string
          slug:
            type: string
          # ...
        required: [title, slug, body, status, authorId, categoryId]
```

### After

```yaml
requestBody:
  content:
    application/json:
      schema:
        type: object
        properties:
          title:
            type: string
            description: "Article title (1-255 chars)"
          slug:
            type: string
            description: "URL slug (lowercase + hyphens)"
          # ...
        required: [title, slug, body, status, authorId, categoryId]
```

## Specification

### Resolution rules

For each property in the openapi schema generated from a `#[Input]` DTO:

1. Look up the matching `@input-param` line on the **Resource method**'s
   docblock by parameter name.
2. If found and it has a description: copy the description into the openapi
   property's `description` field.
3. If not found or no description: fall back to the DTO's **property
   docblock** (as parsed by Issue #1's logic).
4. If still nothing: omit `description` (current behavior).

The Resource method's docblock takes precedence over the DTO's property
docblock — this allows endpoint-specific description overrides.

### Type derivation

This issue does NOT change type derivation. Types continue to come from
DTO reflection (per #76). `@input-param` is **description-only**, never used
as a type source. Rationale: `@input-param` is regenerated from the DTO by
Issue #1's auto-fixer, so type and DTO are guaranteed in sync, but the
description column may be human-edited; description is the only field the
human can meaningfully override per-endpoint.

### Edge cases

- Method with `#[Input]` DTO but no `@input-param` block at all: use DTO
  property docblocks only (Issue #1's `phpcbf` would normally have added
  the block, but we don't depend on that running)
- Method whose `@input-param` lines drift from DTO: trust the DTO for type
  and required-ness; trust `@input-param` for description. If `@input-param`
  has a name that doesn't exist in the DTO, ignore it silently (no warning
  — that's the sniff's job, not the openapi generator's).
- Multiple `#[Input]` parameters on one method (rare but possible): match
  `@input-param` lines to fields by `<dto-class-shortname>.<field>` if name
  collides, else by plain field name. This is a future-proofing edge — emit
  a clear error if it can't disambiguate.

## Acceptance criteria

1. **Round-trip on MyVendor.Cms**: after Issue #1's auto-fixer has populated
   `@input-param` blocks on `Article::onPost`, `onPut`, `Auth::onPost`,
   running `composer doc` produces openapi.json where each input field has
   the expected `description` string.
2. **Override precedence**: if a Resource method's `@input-param` line has a
   different description than the DTO's property docblock, the openapi
   reflects the Resource method's version.
3. **Backwards compatible**: existing #76-generated schemas without
   `@input-param` still produce valid openapi (just without `description`
   fields).
4. **Test fixtures** in BEAR.ApiDoc cover:
   - `@input-param` with description → openapi has it
   - `@input-param` with no description, DTO property has docblock → openapi
     has DTO's
   - Both have descriptions → Resource method's wins
   - Neither has description → openapi omits `description`
5. **No coupling on `bearsunday/coding-standard`**: BEAR.ApiDoc parses
   `@input-param` itself; it does NOT require the coding-standard package
   to be installed. (The two are conceptually paired, but a hard
   composer-require would force every BEAR.ApiDoc user to install sniffs.)

## Out of scope

- Generating `@input-param` blocks (that's Issue #1)
- Adding new openapi schema fields beyond `description`
  (e.g. `format`, `pattern`, `enum` — those should come from the DTO's type
  hints + JSON Schema attributes, not from docblock free text)
- Localization / i18n of descriptions
- BEAR.ApiDoc UI changes (this is purely an openapi-emit change; the UI
  already renders `description` fields)

## Implementation notes for the implementer

- Look in BEAR.ApiDoc for the existing #76 codepath that emits
  `requestBody.content.application/json.schema`. The new logic plugs in
  where each property is being written.
- Use PHP's `ReflectionMethod::getDocComment()` then a docblock parser
  (`phpdocumentor/reflection-docblock` is fine — BEAR.ApiDoc may already
  pull it transitively via dependencies).
- Match `@input-param` lines with this regex (or equivalent parser):
  `^\s*\*\s*@input-param\s+(\S+)\s+\$(\w+)(?:\s+(.+))?$`
  → groups: type, name, description (optional)
- If parse fails, ignore the line silently — Issue #1's sniff handles
  malformed `@input-param` warnings.

## References

- Issue #1 in this roadmap (`001-input-param-expansion.md`)
- `bearsunday/BEAR.ApiDoc#76` — the parent issue (DTO introspection for openapi)
- The codex task currently in flight on `BEAR.ApiDoc#76` (background agent;
  will produce the baseline this issue extends)
- MyVendor.Cms `composer doc` script — the target end-to-end flow that must
  produce the improved openapi output
