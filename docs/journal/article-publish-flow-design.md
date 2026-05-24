# Article publish flow (confirmation as its own resource)

Picks up [Issue #37](https://github.com/bearsunday/MyVendor.Cms/issues/37)
alternatives 3 and 5, and the modernisation-meeting outcome
"confirmation screens are separate resources". The validation-layer
piece of Issue #37 is a separate PR (see
[`validation-layer-design.md`](./validation-layer-design.md)).

---

## Goal

Give the editor a publish gate — a preview-then-commit step — without
folding it into the form-edit page. The form continues to do field
edits; publication moves to a dedicated resource pair (`App` for the
transition, `Page/Admin` for the preview UX).

The point of the separation, from the meeting record:

> 確認画面は、セキュリティリスクを考慮し、機密データの安易なキャッシュや
> 埋め込みを避けるため、個別のリソースとして分離して実装する方針で
> 合意しました。

Translating to code: the confirmation surface gets its own URL, its
own template, and its own caching characteristics. The edit form
doesn't carry a `mode=preview` query string that templates would have
to branch on; the preview's HTML is never embedded into the edit
page.

---

## Resources

### `App\ArticlePublish` — state transition

```text
POST app://self/article-publish {id, publishedAt?}
```

- 200 `{id, slug, status: "published", publishedAt}` on success.
- 404 `{message, id}` when the article does not exist.
- **409** `{message, id, status}` when the article is already
  published. The transition is the resource's contract; "already in
  target state" is a conflict, not a success. Pinning this as 409 (not
  200) is what makes the route safe to retry without hiding bugs.
- `publishedAt` defaults to the current UTC time. Callers can pass an
  ISO-8601 timestamp to backdate publication.

Modelled as its own resource rather than `Article::onPatch` so the
URI carries the state-transition semantics. `Article` stays
verb-rich (POST/PUT/DELETE for full CRUD plus field edits); the
publish move is structurally a separate transition that warrants
its own URI.

`ArticleCommandInterface::publish(int $id, string $status, string $publishedAt)`
is a distinct method from `update(...)`. The publish path only
touches `status` + `published_at`; the smaller surface means audit
hooks, lifecycle events, or per-record authorization can attach to
the publish step without firing on routine field edits.

### `Page\Admin\ArticleConfirm` — preview UX

```text
GET  page://self/admin/articleconfirm?id={id}    -- preview the draft
POST page://self/admin/articleconfirm {id}       -- confirm → publish
```

`GET` renders the draft article in a read-only preview block plus a
publish form. `POST` forwards to `app://self/article-publish` and on
success returns a 303 to the public article URL (`/article?id={id}`).

The preview renders author / category / tag names alongside the body
so the editor can verify the final shape — that's the *raison
d'être* of a confirmation step. Already-published articles render
without the publish form (replaced by a "View public article" link
and a "Back to edit" link) so re-visiting the confirmation URL
doesn't suggest the action is repeatable.

### Edit form → confirm hand-off

`Page\Admin\Article` adds a single link visible only when the
article is in draft state:

```html
<a href="/admin/articleconfirm?id={id}" class="doPublishArticle">Publish article…</a>
```

The form itself stays unchanged — the `status` dropdown still
accepts `published`, and direct `app://` callers can keep using it.
This PR is additive: the confirm flow exists *alongside* the
existing form, not in place of it. We deliberately do **not**
remove the dropdown's `published` option in this PR; doing so would
churn existing tests without strengthening the lifecycle story.

A follow-up could enforce confirm-only publication once the
boundary is settled, but the meeting decision was about separating
the confirmation surface, not about banning the alternate path.

---

## State machine (current)

```text
draft ──doPublishArticle──→ published
```

Single direction for now. Unpublish (`published → draft`) and
archival (`* → archived`) are intentionally not in scope. When they
land they'll either join the existing `ArticlePublish` resource
under a richer `state` field or get their own paired resource —
that decision can wait for a concrete use case.

---

## Why a separate Page resource (not a query param on `Page/Admin/Article`)

Three reasons surfaced when sketching alternatives:

1. **Cache key**. Browsers and proxies cache by URL; a query
   parameter like `?mode=preview` blends preview HTML and edit HTML
   under the same path. Drafts can carry sensitive content (embargo,
   unreleased product copy); the separate URL keeps shared caches
   from accidentally serving preview HTML to a different request
   that hit the same edit URL.
2. **Template safety**. A combined template carries
   `if mode === 'preview'` branches; each branch is a place to leak
   the wrong block. Separate templates compose by addition, not
   subtraction.
3. **Bookmarkability**. A confirmation URL the editor can re-open
   from a notification or chat link is just a `GET`, no form
   submission needed. Folding the preview into the edit page would
   require submitting the form to get back to the preview state.

---

## HAL state-transition links (deferred)

`App\Article::onGet` could expose `goPublishArticle` as a
`#[Link(rel: 'goPublishArticle', ...)]` when `status === 'draft'`,
turning the publish move into a HAL state transition the client
discovers from the article representation. This PR does **not**
add the link — it requires a conditional `#[Link]` (existing
`#[Link]` attributes are unconditional) which is a small framework
shape-shift better tackled in its own change. The App resource
already exists; clients that know the URI can call it now.

---

## Test coverage

- `tests/Resource/App/ArticlePublishTest` — App resource: success,
  explicit `publishedAt`, 404 on missing, 409 on republish.
- `tests/Resource/Page/Admin/ArticleConfirmTest` — Page resource:
  GET preview for draft (form shown), GET on already-published
  (form hidden, notice shown), GET on missing (404), POST happy
  path (303 → public article + state actually moved), POST on
  already-published (409, preview re-rendered), POST on missing
  (404). Two extra cases pin the edit-page link: draft article's
  edit page contains the confirm link, published article's edit
  page does not.
- `tests/Smoke/SqlSmokeTest` and `tests/Smoke/MediaQuerySmokeTest`
  catch missing fixtures (params for `article_publish.sql` and args
  for `ArticleCommandInterface::publish`) — those updates are part
  of this PR.

---

## Out of scope (deferred)

- **Unpublish / archive transitions** — single-direction state
  machine for now; revisit when a concrete use case lands.
- **HAL state-transition `#[Link]`** on `App\Article::onGet` —
  needs conditional-link support; see above.
- **Form-level enforcement** of confirm-before-publish — the
  alternate `status: published` path through the form stays
  available so the test suite and `app://` callers don't churn.
- **ALPS confirm-page descriptor** — added `doPublishArticle` to
  the App profile only; Page-level ALPS for `ArticleConfirm` mirrors
  existing convention (ALPS lives at the App layer).
