# 実践的 Google Auth

[English](../auth-google.md)

この reference CMS では Google を canonical auth path とします。Auth0/OIDC は
secondary provider adapter として残しますが、copy-paste できる tutorial は
default の `CMS_AUTH_PROVIDER=google` に合わせて Google OAuth に寄せます。

## Flow

Page admin は browser OAuth flow です。

1. `GET /admin/login` が session state を発行し、Google へ redirect します。
2. Google が `/admin/callback?code=...&state=...` に戻します。
3. `Callback` が state を consume し、code を exchange して Google user を得ます。
4. `AuthorIdentityResolver` はまず `(provider, subject)` で `auth_identities`
   を探します。
5. identity がなければ `authors.email` を fallback とし、identity mapping を作ります。
6. session に authenticated user と resolved author id を保存します。
7. `AdminGuard` は `authorId` が一致する article だけを管理可能にします。
8. `POST /admin/logout` が session を消し、`/` へ redirect します。

## Google Cloud Setup

Google Cloud project を作成または選択し、OAuth を設定します。

1. **APIs & Services** を開きます。
2. **OAuth consent screen** を設定します。
3. **OAuth client ID** を作成します。
4. 種類は **Web application** を選びます。
5. local callback として次を追加します。

```text
http://127.0.0.1:8081/admin/callback
```

deploy 時は production の HTTPS callback URI も authorized redirect URI に追加します。

## Local `.env`

`.env.dist` を `.env` にコピーし、次を設定します。

```dotenv
CMS_AUTH_PROVIDER=google
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://127.0.0.1:8081/admin/callback
```

fake/test context はこれらの値を使いません。real Page login だけが使います。

## Database and Author Mapping

最初の Google login は author に解決できる必要があります。local では DB を seed し、
`authors.email` のどれかと同じ email の Google account で login します。

```bash
composer sqlite:up
DB_DSN='sqlite:/tmp/bear_cms.db' composer serve
```

ブラウザで開きます。

```text
http://127.0.0.1:8081/admin/login
```

初回 login 成功時に resolver が `auth_identities` row を作ります。

```text
provider = google
subject  = <Google user id>
authorId = <matched authors.id>
email    = <Google email>
name     = <Google display name>
```

以後は `provider + subject` が stable identity key です。email は初回 fallback
だけです。

## Logout

logout は意図的に POST-only です。

```text
POST /admin/logout
```

`GET /admin/logout` は 405 を返し、session を消しません。Admin form post は
same-origin と synchronizer-token CSRF check で保護します。

## Env-Gated Smoke

Google env vars を設定している環境では次を実行します。

```bash
vendor/bin/phpunit tests/Smoke/GoogleAuthProviderSmokeTest.php
```

`GOOGLE_CLIENT_ID`、`GOOGLE_CLIENT_SECRET`、`GOOGLE_REDIRECT_URI` がなければ
test は skip します。Google への network call は行わず、real provider が expected
client id、redirect URI、state、scope を含む authorization URL を作れることだけを見ます。

## Common Failures

| Symptom | Likely cause | Fix |
|---|---|---|
| Google が `redirect_uri_mismatch` を出す | Google Cloud の callback URL と `.env` が完全一致していない | OAuth client に exact `GOOGLE_REDIRECT_URI` を追加する |
| `/admin/callback` が 401 | code がない、session が古い、state 不一致、token exchange failure | `/admin/login` からやり直し、cookie と callback URI を確認する |
| `/admin/callback` が 403 | Google email に対応する `authors.email` も identity mapping もない | その email の Author row を追加/seed するか、`auth_identities` を事前作成する |
| Admin page が 401 | admin session がない | 再 login する |
| Admin edit/delete が 403 | login author が article owner ではない | `authorId` が一致する article を使う |
| GET logout で何も起きない | logout は POST-only | logout form または `POST /admin/logout` を使う |

## Auth0/OIDC Position

Auth0/OIDC は tenant-backed identity 用の secondary adapter として残します。

```dotenv
CMS_AUTH_PROVIDER=auth0
AUTH0_DOMAIN=...
AUTH0_CLIENT_ID=...
AUTH0_CLIENT_SECRET=...
AUTH0_REDIRECT_URI=http://127.0.0.1:8081/admin/callback
AUTH0_AUDIENCE=
AUTH0_COOKIE_SECRET=...
```

同じ `AuthenticatedUser` と `auth_identities` mapping shape を使います。別 provider
は、異なる BEAR.Sunday boundary を教える場合だけ追加します。
