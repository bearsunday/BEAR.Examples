# Coding-standard roadmap

Issue drafts for the `bearsunday/coding-standard` package (and follow-ups
in adjacent repos). Each file is GitHub-issue-ready: paste into
`gh issue create -R <repo> -F <file>` once the destination repos are set up.

## Status

| # | Title | Target repo | Status |
|---|---|---|---|
| v0.1 | Initial release: 4 custom sniffs + Doctrine extend | `bearsunday/coding-standard` | **In flight** (codex agent) |
| 001 | `@input-param` docblock expansion for `#[Input]` DTOs | `bearsunday/coding-standard` | Drafted |
| 002 | BEAR.ApiDoc reads `@input-param` for OpenAPI description | `bearsunday/BEAR.ApiDoc` | Drafted |
| 003 | MyVendor.Cms migrates to `bearsunday/coding-standard` | `MyVendor.Cms` | Drafted |

## Order of execution

```
v0.1 (codex, in flight)
  ├─→ 001 (codex, after v0.1 lands)
  │    └─→ 002 (codex, after 001 + BEAR.ApiDoc#76 land)
  └─→ 003 (codex, after v0.1 lands; can run parallel with 001 if a path repo is used)
```

## Workflow

1. Wait for v0.1 codex agent to complete and report back
2. Verify v0.1 deliverable (composer install, phpunit, ruleset application
   on MyVendor.Cms — see v0.1 prompt for exact verification commands)
3. Once v0.1 is on GitHub (user does `gh repo create bearsunday/coding-standard`),
   file the three drafted issues:
   ```bash
   gh issue create -R bearsunday/coding-standard \
       -F docs/journal/coding-standard-roadmap/001-input-param-expansion.md
   gh issue create -R bearsunday/BEAR.ApiDoc \
       -F docs/journal/coding-standard-roadmap/002-apidoc-input-param-integration.md
   # Issue #003 lives in MyVendor.Cms — file it on this repo
   gh issue create -R koriym/MyVendor.Cms \
       -F docs/journal/coding-standard-roadmap/003-myvendor-cms-adopts-bearsunday-cs.md
   ```
4. Hand each issue to a codex agent in turn (one issue per agent, with the
   issue body as the prompt and the verification criteria as the
   completion gate)
