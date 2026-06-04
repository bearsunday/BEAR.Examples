# Practical Google Auth

[日本語](ja/auth-google.md)

Google is the canonical auth path for this reference CMS. Auth0/OIDC remains a
secondary provider adapter, but the copy-pasteable tutorial is Google OAuth
because it matches the default `CMS_AUTH_PROVIDER=google` configuration.

## Flow

The Page admin uses a browser OAuth flow:

1. `GET /admin/login` issues a session state and redirects to Google.
2. Google redirects back to `/admin/callback?code=...&state=...`.
3. `Callback` consumes the state, exchanges the code, and receives a Google
   user.
4. `AuthorIdentityResolver` first checks `auth_identities` by
   `(provider, subject)`.
5. If no identity exists yet, it falls back to `authors.email` and creates the
   identity mapping.
6. The session stores the authenticated user and the resolved author id.
7. `AdminGuard` lets the user manage only articles whose `authorId` matches
   that author id.
8. `POST /admin/logout` clears the session and redirects to `/`.

## Google Cloud Setup

Create or choose a Google Cloud project, then configure OAuth:

1. Open **APIs & Services**.
2. Configure the **OAuth consent screen**.
3. Create an **OAuth client ID**.
4. Choose **Web application**.
5. Add this local redirect URI:

```text
http://127.0.0.1:8081/admin/callback
```

Add the production HTTPS callback URI as another authorized redirect URI when
you deploy.

## Local `.env`

Copy `.env.dist` to `.env` and set:

```dotenv
CMS_AUTH_PROVIDER=google
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://127.0.0.1:8081/admin/callback
```

The default fake/test contexts ignore these values. Real Page login uses them.

## Database and Author Mapping

The first successful Google login must resolve to an author. For local testing,
seed the database and use a Google account whose email matches one row in
`authors.email`:

```bash
composer sqlite:up
DB_DSN='sqlite:/tmp/bear_cms.db' composer serve
```

Open:

```text
http://127.0.0.1:8081/admin/login
```

On first successful login, the resolver creates an `auth_identities` row:

```text
provider = google
subject  = <Google user id>
authorId = <matched authors.id>
email    = <Google email>
name     = <Google display name>
```

After that, `provider + subject` is the stable identity key. Email is only the
first-login fallback.

## Logout

Logout is intentionally POST-only:

```text
POST /admin/logout
```

`GET /admin/logout` returns 405 and does not clear the session. Admin form posts
are protected by same-origin and synchronizer-token CSRF checks.

## Env-Gated Smoke

With Google env vars configured, run:

```bash
vendor/bin/phpunit tests/Smoke/GoogleAuthProviderSmokeTest.php
```

Without `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, or `GOOGLE_REDIRECT_URI`,
the test skips. It does not call Google over the network; it verifies that the
real provider can generate an authorization URL with the expected client id,
redirect URI, state, and scopes.

## Common Failures

| Symptom | Likely cause | Fix |
|---|---|---|
| Google shows `redirect_uri_mismatch` | The callback URL in Google Cloud does not exactly match `.env` | Add the exact `GOOGLE_REDIRECT_URI` to the OAuth client |
| `/admin/callback` returns 401 | Missing code, stale session, bad state, or failed token exchange | Start again from `/admin/login`; confirm cookies and callback URI |
| `/admin/callback` returns 403 | Google email does not match any `authors.email` row and no identity mapping exists | Add or seed an Author row for that email, or pre-create `auth_identities` |
| Admin page returns 401 | No active admin session | Log in again |
| Admin edit/delete returns 403 | Authenticated author does not own the article | Use an article whose `authorId` matches the logged-in author |
| Logout via GET does nothing | Logout is POST-only | Submit the logout form or POST to `/admin/logout` |

## Auth0/OIDC Position

Auth0/OIDC remains useful as a secondary adapter for tenant-backed identity:

```dotenv
CMS_AUTH_PROVIDER=auth0
AUTH0_DOMAIN=...
AUTH0_CLIENT_ID=...
AUTH0_CLIENT_SECRET=...
AUTH0_REDIRECT_URI=http://127.0.0.1:8081/admin/callback
AUTH0_AUDIENCE=
AUTH0_COOKIE_SECRET=...
```

It uses the same `AuthenticatedUser` and `auth_identities` mapping shape. Do
not add more providers unless they teach a different BEAR.Sunday boundary.
