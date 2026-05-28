# CSRF defence design

This note records the cross-site request defence layered into the admin
surface. Lives next to
[`validation-layer-design.md`](validation-layer-design.md) and
[`auth-boundary-plan.md`](auth-boundary-plan.md) — together they cover
admin-side request handling end to end.

The choreography comes from
[Issue #37](https://github.com/bearsunday/MyVendor.Cms/issues/37) (Form /
Confirmation / CSRF strategy) and the Codex review pass that followed.

---

## Goal

Reject browser-driven unsafe HTTP verbs that originate from outside the
deployment's own origin. The classic CSRF concern: a cookie-authenticated
admin visiting an unrelated page should not have their cookies
weaponised to issue `POST /admin/articledelete?id=1` from
`https://evil.example`.

The defence is **not** a substitute for identity:
- Identity comes from the session cookie (see
  [`auth-boundary-plan.md`](auth-boundary-plan.md)).
- This layer says "the browser that sent this request was on our site
  when it was sent" — nothing more.

Both checks have to pass before a write reaches the resource method.

---

## Architecture

Two attributes layer the defence:

| Attribute | What it requires | Where |
|---|---|---|
| `#[SameOrigin]` | Browser-emitted same-origin signals (`Sec-Fetch-Site`, `Origin`, `Referer`) match the configured allowed origin. | All Page/Admin `onPost` methods. |
| `#[CsrfToken]` | A per-session token, submitted in the configured form-body field (`CsrfTokenField`, default `_csrf_token`), matches the one stored in session. | Destructive / session-changing onPost methods: `Article`, `ArticleDelete`, `ArticleConfirm`, `Logout`. |

The two layers stack — `#[SameOrigin]` defends the bulk of cookie-driven
CSRF cheaply; `#[CsrfToken]` adds belt-and-braces protection on the
operations where a same-origin compromise (XSS in a sibling subdomain,
sloppy `SameSite` defaults on a related origin, etc.) would do the most
damage.

Naming follows the @NaokiTsuchiya note in Issue #37: the attribute
describes **what the resource requires**, not **the attack it defends
against**. `#[SameOrigin]` reads as a precondition; `#[Csrf]` would
read as a defence implementation detail.

---

## Wiring

`CsrfModule` (`src/Module/CsrfModule.php`) binds both interceptor
pointcuts and the values they depend on. `AppModule` installs the
module after the auth bindings, passing the operator-controlled
configuration through the constructor:

```php
$allowedOrigin = ((string) getenv('CMS_ALLOWED_ORIGIN')) ?: null;
$this->install(new CsrfModule($allowedOrigin));
```

The two configuration values flow as typed value classes, bound via
`toInstance`:

- **`AllowedOrigin`** — the canonical origin browsers must match. `null`
  short-circuits both gates (see "Short-circuit mode").
- **`CsrfTokenField`** — the form-body field the token is read from
  and rendered into (defaults to `_csrf_token`). Single source of
  truth for `ServerRequestBodyToken` and the four Qiq templates that
  render `<input name="…">`.

Inbound HTTP state goes through interfaces so tests can override
without scripting `$_SERVER` / `$_POST`:

- **`RequestOriginInterface`** — wraps the request headers
  (`Sec-Fetch-Site`, `Origin`, `Referer`).
- **`RequestBodyTokenInterface`** — wraps the submitted token field.

`FakeModule` rebinds both interfaces to fakes and pins
`AllowedOrigin` to `null`, so the test / CLI / fake-app contexts
short-circuit by default. Tests that want to exercise the gates
rebind `AllowedOrigin` (and the relevant header / body fake)
locally via `Injector::getOverrideInstance()`.

---

## `#[SameOrigin]` runtime

`SameOriginInterceptor` walks three signals in priority order:

1. **`Sec-Fetch-Site`** (Fetch Metadata). A forbidden request header —
   browser-generated, not settable from JS — so `same-origin` is
   authoritative. Anything else (`same-site`, `cross-site`, `none`,
   or an unknown literal) → `ForbiddenException` (403). Unknown
   values are rejected rather than fallthrough: a surprising value
   is more likely an attack than a new browser literal.
2. **`Origin`**. Compared as a *canonical origin* (scheme + host
   lowercased per RFC 3986, default ports collapsed, no path / query
   / fragment / userinfo). Mismatch → 403. Malformed (parse failure,
   `Origin: null`, anything with a path beyond `/`) →
   `BadRequestException` (400). 400 means "your request is wrong";
   403 means "we won't process this request".
3. **`Referer`**. Same canonical comparison after extracting the URL's
   origin component. Used only when both `Sec-Fetch-Site` and
   `Origin` are absent.

All three absent (with `AllowedOrigin->value` non-null) → 403
(fail-closed). `AllowedOrigin->value === null` short-circuits at the
top of `invoke()` — see "Short-circuit mode".

---

## `#[CsrfToken]` runtime

`CsrfTokenInterceptor` is the second layer, applied only to
destructive / session-changing POSTs:

1. Read the submitted token from `RequestBodyTokenInterface::submitted()`
   (production: `$_POST[CsrfTokenField->name]`). Missing →
   `ForbiddenException` "CSRF token missing.".
2. Constant-time compare via `CsrfTokenInterface::verify($submitted)`.
   Mismatch (or no token ever issued — fresh session, post-logout) →
   `ForbiddenException` "CSRF token invalid.".

Both branches surface as 403; the distinct messages help debugging
but are not differentiated to the client. `AllowedOrigin->value === null`
short-circuits the same way as `#[SameOrigin]`.

Token lifecycle:

- `CsrfTokenInterface::issue()` is idempotent within a session —
  generates 256-bit hex on first call, returns the stored value
  thereafter. Per-request rotation is intentionally not done so a
  form rendered at GET and submitted at POST sees the same value.
- `AuthSessionInterface::logout()` calls `CsrfTokenInterface::clear()`,
  so a fresh session (re-login as a different admin, repeat login
  as the same admin) starts with a new token.
- `CmsQiqRenderer::commonVars()` exposes both `csrfToken` (the
  value) and `csrfTokenField` (the field name) to every template,
  so layout-level forms (sign-out in the nav) embed the hidden
  field without each Page resource pushing them into `$this->body`.

---

## Short-circuit mode

`AllowedOrigin->value === null` disables **both** gates. The
test / CLI / fake-app shape — no browser on the other side, so
no origin signals or `_csrf_token` would be populated; enforcing
the checks would always fail-closed and block legitimate CLI
invocations of admin Page resources for no security benefit. Tying
both gates to the same on/off knob keeps the mental model
"production HTTP enforces, everywhere else skips" in one config
value.

**Production gotcha.** Because `null` means "skip", a production
HTTP deployment that forgets to set `CMS_ALLOWED_ORIGIN` silently
disables both gates. The fail-closed path belongs in a `ProdModule`
that aborts at boot if the env var is missing — out of scope for
this PR. Tracked under [docs/scope.md](../scope.md) Tier 2.

---

## What this layer doesn't do

- **Session cookie flags** (`SameSite=Lax`, `Secure`, `HttpOnly`)
  belong on the cookie issuer, not on the request gate.
  Complementary defences — neither replaces the other.
- **Per-request token rotation.** The synchroniser token persists
  for the session's lifetime (cleared on logout). Rotating per
  request would break the round-trip between GET form render and
  POST submit.
- **Session id rotation on login / logout.** Belongs with the auth
  session layer; the CSRF clear is a separate concern from session
  fixation hardening.

---

## Why on Page/Admin, not on App

Both attributes are attached only to `Page/Admin/*::onPost`. The App
resources (`app://self/article` etc.) stay unguarded:

- App resources are routinely invoked from the CLI, from seeds, from
  Page-layer composition — contexts where there is no HTTP request
  and no headers to check. A gate that assumed HTTP would break
  those callers.
- The Page layer is the public surface for browsers. A CSRF attack
  reaches the server through a browser submitting to a Page
  resource, not through a process calling
  `$resource->post('app://self/article')` directly.

`AdminPageCsrfAttributeCoverageTest` fails CI if a new admin
`onPost` ships without both attributes — closes the "you added a
new admin POST and forgot the attribute" gap.

---

## Test coverage

- **`tests/Interceptor/SameOriginInterceptorTest`** — 18 unit cases
  over the algorithm: allowed-origin null short-circuit, every
  `Sec-Fetch-Site` literal (including unknown reject), `Origin`
  match / mismatch / canonicalisation (default port, case), malformed
  / `null`-literal / with-path, `Referer` match / mismatch /
  malformed, all-signals-missing fail-closed, malformed-allowed-
  origin configuration fail-closed.
- **`tests/Interceptor/CsrfTokenInterceptorTest`** — 5 unit cases:
  allowed-origin-null short-circuit, matching / missing / mismatched
  token, empty-stored-empty-submitted does not collapse to equality.
- **`tests/Interceptor/SameOriginWiringTest`,
  `CsrfTokenWiringTest`** — one end-to-end case each. A cross-site
  POST (or missing-token POST) through the DI container to
  `page://self/admin/logout` raises `ForbiddenException`. Proves
  the attribute matches, the interceptor is bound, and the fake
  overrides reach the runtime. Other annotated `onPost` methods
  share the wiring path.
- **`tests/Interceptor/AdminPageCsrfAttributeCoverageTest`** —
  reflection-based; walks `Page/Admin/*` for unsafe verbs and
  asserts both attributes are present. Dynamic data provider, so
  new admin POSTs are picked up automatically.

The unit / wiring split is intentional: unit tests are hermetic and
fast; the wiring tests are the safety net that catches a stale
module binding before CI does.

---

## Out of scope

- `X-CSRF-Token` request header support — no JS-driven write surface
  yet; the body-only path is sufficient for the four annotated POSTs.
- `ProdModule` boot-time fail-closed when `CMS_ALLOWED_ORIGIN` is
  unset — see [docs/scope.md](../scope.md) Tier 2.
- Rendering 403 as a styled HTML error page through `HtmlModule`'s
  error pipeline. The interceptors throw `ForbiddenException`; the
  default error pipeline turns that into a 4xx response. A polished
  HTML 403 page belongs with the broader Page-layer error UX work.
- Extracting the interceptors into a `ray/csrf-module` package —
  waits until the in-app shape settles.
