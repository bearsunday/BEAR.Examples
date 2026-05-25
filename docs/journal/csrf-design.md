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
| `#[CsrfToken]` | A per-session token submitted as `_csrf_token` in the form body matches the one stored in session. | Destructive / session-changing onPost methods: `Article`, `ArticleDelete`, `ArticleConfirm`, `Logout`. |

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

## `#[SameOrigin]` runtime

### Wiring

`CsrfModule` (`src/Module/CsrfModule.php`) binds the interceptor
pointcut + the two interfaces it depends on. `AppModule` installs the
module after the auth bindings; FakeModule rebinds the interfaces to
in-memory fakes so tests / CLI / fake-app don't have to script HTTP
headers.

`RequestOriginInterface` and `AllowedOriginInterface` are deliberately
separate. The first models inbound HTTP headers (untrusted, client-
controlled); the second models server configuration (trusted, set by
the operator). Conflating them would let one fake script both sides
and obscure that they're different concerns.

### Detection algorithm

In priority order:

1. **`Sec-Fetch-Site`** (Fetch Metadata, RFC-adjacent). It's a
   forbidden request header — browser-generated, not settable from
   JS — so a `same-origin` value is authoritative. Anything else
   (`same-site`, `cross-site`, `none`, or an unknown literal) is
   rejected as `ForbiddenException` (403). Unknown values are
   treated as cross-site rather than fallthrough; a surprising
   value is more likely an attack than a new browser literal we
   should trust.

2. **`Origin`**. Compared as a *canonical origin* (scheme + host
   lowercased per RFC 3986, default ports for the scheme collapsed,
   no path / query / fragment / userinfo). Mismatch → 403.
   Malformed (parse failure, `Origin: null`, anything with a path
   beyond `/`) → `BadRequestException` (400). The 400/403 split
   reflects RFC semantics: 400 is "your request is wrong", 403 is
   "we won't process this request".

3. **`Referer`**. Same canonical comparison after extracting the URL's
   origin component. Used only when both `Sec-Fetch-Site` and
   `Origin` are absent.

4. **All three absent**. With `AllowedOriginInterface::value()` non-
   null, the request is rejected as 403 (fail-closed). With `value()`
   null the gate short-circuits at the top of the interceptor and
   never reaches this branch.

### Short-circuit mode

`AllowedOriginInterface::value() === null` skips the gate entirely.
That's the test / CLI / fake-app shape — none of those have a browser
on the other side, so their requests can't have origin signals to
check.

**Production gotcha.** Because `null` means "allow", a production HTTP
deployment that forgets to set `CMS_ALLOWED_ORIGIN` silently disables
the gate. The fail-closed path lives in a `ProdModule` that aborts at
boot if the env var is missing — that module isn't in scope for this
PR. Tracked under [docs/scope.md](../scope.md) Tier 2 "Production
tuning notes"; see also `AllowedOriginInterface`'s docblock for the
in-code warning.

### What this layer doesn't do

- **Session cookie flags** (`SameSite=Lax`, `Secure`, `HttpOnly`)
  belong on the cookie issuer, not on the request gate. The
  interceptor reads inbound headers; cookie flags are an outbound
  concern, set by the auth session layer. They're complementary
  defences — neither replaces the other.
- **CSRF tokens** (synchroniser tokens posted as a hidden field).
  That's `#[CsrfToken]`, landing in PR-B2.

---

## Why on Page/Admin, not on App

`#[SameOrigin]` is attached only to `Page/Admin/*::onPost`. The App
resources (`app://self/article` etc.) deliberately stay unguarded:

- App resources are routinely invoked from the CLI, from seeds, from
  Page-layer composition — contexts where there is no HTTP request
  and no headers to check. An interceptor that assumed an HTTP
  request would break those callers.
- The Page layer is the public surface for browsers. Putting the gate
  there matches the threat: a CSRF attack reaches the server through
  a browser submitting to a Page resource, not through a process
  calling `$resource->post('app://self/article')` directly.

This is the Codex review's most important pre-implementation
correction — the original sketch put `#[SameOrigin]` on the App
methods and would have broken every internal use of those resources.

---

## Test coverage

- **`tests/Interceptor/SameOriginInterceptorTest`** — 18 cases over
  the algorithm: allowed-origin null short-circuit, every
  `Sec-Fetch-Site` literal (including the explicit reject for
  unknown values), `Origin` match / mismatch / canonicalisation
  (default port, case), `Origin` malformed / `null`-literal /
  with-path, `Referer` match / mismatch / malformed,
  all-signals-missing fail-closed, malformed-allowed-origin
  configuration fail-closed.
- **`tests/Interceptor/SameOriginWiringTest`** — one end-to-end
  case: a cross-site POST through the DI container (with
  `#[SameOrigin]`-annotated `Page/Admin/Logout::onPost`) raises
  `ForbiddenException`. Proves the attribute matches, the
  interceptor is bound, and the fake overrides reach the runtime.
  Other annotated `onPost` methods share the wiring path, so one
  case is enough.

The unit and wiring split is intentional: unit tests are hermetic and
fast; the wiring test is the safety net that catches a stale module
binding before CI does.

---

## Out of scope

- `#[CsrfToken]` interceptor (PR-B2).
- `ProdModule` boot-time fail-closed when `CMS_ALLOWED_ORIGIN` is
  unset — see `docs/scope.md` Tier 2.
- Rendering 403 as a styled HTML error page through `HtmlModule`'s
  error pipeline. The interceptor throws `ForbiddenException`; the
  default error pipeline turns that into a 4xx response. A polished
  HTML 403 page belongs with the broader Page-layer error UX work.
- Extracting the interceptor into a `ray/csrf-module` package. Per
  the Codex review, package extraction waits until the in-app
  shape settles.
