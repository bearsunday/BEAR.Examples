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
`AdminUser` carries `authorId` so resources can do per-record
authorization (see "Per-record authorization" below).

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
  `<?php if ($user instanceof AdminUserInterface): ?>` to show a link
  to `Page/Admin/Index` (`/admin`).
- `Page/Admin/Index` (admin landing): `AdminUserInterface $admin` →
  link hub for the admin pages (`ArticleList`, new-article form,
  future Author/Category admin). This is the destination of the
  conditional link from `Page/Index` and the canonical post-login
  landing page.
- `Page/Admin/Article` / `ArticleDelete` / `ArticleList` (admin-only):
  `AdminUserInterface $admin` → no branching in the resource. If the
  user is not admin, construction throws and the request fails before
  `onGet` runs.

---

## Per-record authorization

Authentication only proves "you are some admin"; it doesn't bound
*which* records you may mutate. Without an ownership check, any
signed-in admin could edit/delete anyone else's article.

Model: **each admin owns the articles whose `authorId` matches their
own `AdminUser->authorId`**. No editor/super-admin role yet — flat
per-author ownership, the natural model for a multi-author blog.

Enforcement points (`AdminUserInterface $admin` is already injected,
so the check is a one-liner against `$admin->authorId`):

- `Page/Admin/Article::onGet(int $id)` — load article, if
  `article.authorId !== admin.authorId` → 403 (or 404 to avoid
  leaking existence; pick one and document it).
- `Page/Admin/Article::onPost(int $id, ...)` (update path) — same
  check before delegating to `app://self/article` PUT.
- `Page/Admin/ArticleDelete::onPost` — same check before DELETE.
- `Page/Admin/ArticleList::onGet` — filter the listing to
  `where authorId = admin.authorId` so admins only see their own
  drafts. (Public `Page/ArticleList` is unaffected.)
- Create (`onPost` with no id) is implicitly safe: `authorId` is
  taken from `$admin`, not from the form. The `defaultAuthorId()`
  fallback in `Page/Admin/Article` (added in PR #18 as a
  pre-OAuth stub) is removed in this PR — `$admin->authorId`
  replaces it.

Tests to add:

- admin A can GET/POST/DELETE their own article → 200/303
- admin A trying the same on admin B's article → 403 (or 404)
- admin A's `/admin/articlelist` does not contain admin B's
  article slug

The App-layer resources (`app://self/article`) stay
authorization-agnostic — they are pure CRUD over the DB and can be
called from CLI/seed scripts without an `AdminUser`. The Page layer
is where the policy lives.

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
- Replace the PR #18 redirect in `Page/Admin/Index`
  (`page://self/admin/index`) with a real admin landing page that
  injects `AdminUserInterface`. Link hub to the other admin pages;
  the canonical first example of "type-driven admin gate".
- Constructor changes for every existing `Page/Admin/*` resource
  (`Article`, `ArticleDelete`, `ArticleList`) to inject
  `AdminUserInterface`
- Per-record ownership checks in `Page/Admin/Article` (GET + update
  POST), `Page/Admin/ArticleDelete`, and `Page/Admin/ArticleList`
  (filtered listing). Drop the `defaultAuthorId()` stub from
  `Page/Admin/Article::onPost` — replace with `$admin->authorId`.
- `Page/Index` body + template change to show admin link
  (`/admin` → `Page/Admin/Index`) conditionally
- Tests:
  - existing admin tests pass with Fake `AdminUser` injection
  - new test: visitor sees no admin link on `/`
  - new test: admin user sees admin link on `/`
  - new test: admin user can GET `/admin` (Index renders)
  - new test: visitor hitting `Page/Admin/*` (any of them) results in
    `UnauthenticatedException` (or 401, depending on how we surface
    construction failure to the response)
  - new tests: admin A can edit/delete their own article (200/303);
    admin A on admin B's article returns 403 (or 404); admin A's
    `/admin/articlelist` excludes admin B's articles

## Out of scope (deliberately deferred)

- Real Google OAuth plumbing (the provider stays a stub)
- Login / logout pages
- Session persistence beyond what's needed to demo the boundary
- `User`-required (non-admin-only) pages
- Editor / super-admin roles that can edit anyone's article (a
  flat per-author ownership model is enough for the sample)

---

## How to resume in a new session

> "Implement `docs/journal/auth-boundary-plan.md`. Target branch:
> `auth-boundary` off `1.x`. Don't touch PR #18."

That alone is enough — this doc is the brief.
