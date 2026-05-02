# Auth boundary plan (follow-up to PR #18)

PR #18 left `Page/Admin/*` reachable by anyone. The minimal fix
(removing the public link) shipped in PR #18; this doc records the
real design, to be implemented in a separate PR.

---

## Goal

Express "authenticated admin" at the **type level** so admin
resources contain zero `if ($user === null)` branches. No real login
UI yet — only the boundary into which a future OAuth/session layer
plugs.

---

## Type hierarchy

```text
UserInterface
  ├─ Visitor              (unauthenticated)
  ├─ User                 (authenticated, non-admin) — placeholder, not yet used
  └─ AdminUserInterface   (authenticated admin)
       └─ AdminUser
```

`UserInterface` **always** has an instance — `Visitor` is the null
object for "not logged in". No nullable, no exception, on this
interface.

`AdminUserInterface` is provided **only when** the current user is
an admin; otherwise the provider throws `UnauthenticatedException`.

---

## DI bindings

Two providers, both reading the same session/OAuth state:

1. **`UserInterface` provider** — returns one of `Visitor` / `User` /
   `AdminUser` based on session. Never null, never throws.
2. **`AdminUserInterface` provider** — calls (1), checks
   `instanceof AdminUserInterface`, returns it or throws
   `UnauthenticatedException`. Constructor injection of
   `AdminUserInterface` therefore *guarantees* admin at the type
   level.

Future Google OAuth hook = swap the body of provider (1).

---

## Injection sites

- `Page/Index` (public): `UserInterface $user` → template uses
  `<?php if ($user instanceof AdminUserInterface): ?>` to show the
  admin link.
- `Page/Admin/*` (admin-only): `AdminUserInterface $admin` → no
  branching in the resource. If the user is not admin, construction
  throws and the request fails before `onGet` runs.

---

## Contexts

| Context | `UserInterface` provider returns |
|---------|----------------------------------|
| `hal-api-app` / `html-hal-app` (production) | `Visitor` (until OAuth lands) |
| `cli-*` | `Visitor` (CLI has no session) |
| `fake-hal-api-app` | configurable; default `AdminUser` so demos work |
| `test-hal-api-app` (App resource tests) | `Visitor` |
| `html-test-hal-api-app` (Page tests) | `AdminUser` for `Page/Admin/*` tests, `Visitor` for `Page/Index` admin-link-hidden test |

For per-test overrides, use a Fake module that lets tests bind a
specific `UserInterface` instance.

---

## In scope for the follow-up PR

- `Entity\Visitor`, `Entity\User`, `Entity\AdminUser`
- `UserInterface`, `AdminUserInterface`
- Two providers + module wiring across `AppModule` / `FakeModule` /
  `TestModule`
- Constructor changes for every `Page/Admin/*` resource
- `Page/Index` body + template change to show admin link conditionally
- Tests:
  - existing admin tests pass with Fake `AdminUser` injection
  - new test: visitor sees no admin link on `/`
  - new test: admin user sees admin link on `/`
  - new test: visitor hitting `Page/Admin/*` results in
    `UnauthenticatedException` (or 401, depending on how we surface
    construction failure to the response)

## Out of scope (deliberately deferred)

- Real Google OAuth plumbing (the provider stays a stub)
- Login / logout pages
- Session persistence beyond what's needed to demo the boundary
- `User`-required (non-admin-only) pages

---

## How to resume in a new session

> "Implement `docs/journal/auth-boundary-plan.md`. Target branch:
> `auth-boundary` off `1.x`. Don't touch PR #18."

That alone is enough — this doc is the brief.
