# MyVendor.Cms migrates to `bearsunday/coding-standard`

**Repo:** `MyVendor.Cms` (the reference implementation itself)
**Depends on:** `bearsunday/coding-standard` v0.1 published (or at minimum, locally installable via path repository)
**Priority:** High — closes the loop between standard and reference

## Background

`bearsunday/coding-standard` v0.1 is being built (currently in flight via codex).
Once published, MyVendor.Cms — the reference implementation that motivated
many of the rules — should adopt the standard. This validates the package
in a real consumer and removes duplicated rule definitions from
MyVendor.Cms's local `phpcs.xml`.

The migration also serves as **proof that a project can adopt the standard
without project-specific overrides for the established rules**. If MyVendor.Cms
needs hacks to use the standard, that's a signal the standard itself needs
adjustment — and we'd rather discover that here than at a downstream user.

## Goal

`MyVendor.Cms/phpcs.xml` becomes minimal:

```xml
<?xml version="1.0"?>
<ruleset name="MyVendor.Cms">
    <description>MyVendor.Cms uses the BEAR.Sunday coding standard.</description>
    <arg name="basepath" value="."/>
    <arg name="extensions" value="php"/>
    <arg name="parallel" value="80"/>
    <arg name="cache" value=".phpcs-cache"/>
    <config name="php_version" value="80505"/>
    <arg value="nps"/>

    <file>src</file>
    <file>tests</file>
    <exclude-pattern>*/tests/tmp/*</exclude-pattern>

    <rule ref="BearSunday"/>

    <!-- Project-specific overrides (only if genuinely needed) -->
</ruleset>
```

`composer.json` `require-dev` adds `bearsunday/coding-standard`. The current
inline Doctrine reference + Slevomat exclusions + custom rule blocks are
all removed (they live in the standard now).

## Specification

### Migration steps

1. Add `bearsunday/coding-standard: "^0.1"` to `composer.json` `require-dev`
2. Replace `phpcs.xml` body with `<rule ref="BearSunday"/>` plus only
   project-specific overrides
3. Run `composer cs` — expect zero violations (this is the validation)
4. If violations appear, categorize them:
   - **Real new violations** (the standard catches something the project
     was previously sneaking past) → fix the project code
   - **False positives** (standard is wrong) → file an issue against the
     standard, add narrowly-scoped local override with a TODO and a link
   - **Project-legitimate exceptions** (e.g. `tests/Fake/` was already
     excluded) → keep the override, document why in a comment
5. Update `CLAUDE.md` `## PHP` section to mention the standard is the
   source of truth
6. Update `docs/conventions.md` to cross-reference the standard for
   enforcement (rather than describing every rule inline — keep rationale
   here, enforcement detail in the standard)

### Validation

Full `composer tests` (cs, sa, phpstan, phpmd, phpunit) must pass after
migration. No phpstan baseline regressions; no phpmd baseline regressions.

### What stays in MyVendor.Cms

- Project-specific exclusions (e.g. `tests/Fake/*` if still relevant after
  the `src/Fake/` move that already happened on `post-demo-fixes`)
- Project-specific includes (e.g. patterns that only apply to the demo
  app's structure)
- The `php_version` config (project sets its own minimum)

### What moves out

- `<rule ref="Doctrine">` block
- All Slevomat exclusions (now in the standard)
- All custom rules added during reference-implementation work that ended
  up in the standard

## Acceptance criteria

1. `phpcs.xml` is < 25 lines (down from ~75)
2. `composer cs` passes with the new minimal config
3. `composer tests` passes (no regression)
4. `composer.json` lists `bearsunday/coding-standard` in `require-dev`
5. `CLAUDE.md` mentions the standard
6. `docs/conventions.md` cross-references the standard at the top
7. The PR description for this migration includes a "before/after" diff
   of `phpcs.xml` (showing the cleanup) and a note on any local overrides
   retained (with reasons)

## Out of scope

- Adopting the standard in other repos (BEAR.Skeleton, BEAR.Hello, etc.)
  — those are separate consideration, possibly separate issues
- Migrating MyVendor.Cms's `phpmd.xml` to a hypothetical
  `bearsunday/phpmd-standard` (no such standard exists yet; not in scope)
- BEAR.QATools alignment with the new standard (BEAR.QATools currently
  bundles `doctrine/coding-standard` — whether to switch to
  `bearsunday/coding-standard` is a BEAR.QATools concern, separate issue)

## Implementation notes for the implementer

- Do this work on a feature branch off `1.x` (per project convention,
  never commit to `1.x` directly)
- Branch suggestion: `adopt-bearsunday-coding-standard`
- Run `vendor/bin/phpcs` (NOT `composer cs`) during iteration to see raw
  output without other tooling noise
- For local development before `bearsunday/coding-standard` is on Packagist,
  use a composer path repository:
  ```json
  "repositories": [
      {"type": "path", "url": "../bearsunday-coding-standard"}
  ]
  ```
  Remove this before merging; rely on Packagist for the actual release
- Expect the `Generic.PHP.ForbiddenFunctions` rule (added in the standard)
  to catch any stray `var_dump` / `print_r` / `dd` / `error_log` usage —
  fix any hits as part of this migration

## References

- `bearsunday/coding-standard` (the new repo — not yet published)
- Issue #1 (`@input-param` expansion) — should land before #3 if its
  sniff is going to be enforced on MyVendor.Cms's Article/Auth resources
- MyVendor.Cms current `phpcs.xml`
- MyVendor.Cms `docs/conventions.md` — content stays, scope of "what's
  enforced where" updates
