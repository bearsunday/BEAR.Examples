# `auth-oauth-flow`

**OAuth認証フローをResourceで示す** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `showcase`
- **Aliases:** OAuth, OAuth2, Auth0, Google login, AuthInterface, authorization URL, token exchange, league/oauth2-client, 認証, ソーシャルログイン
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** OAuth provider（Google, Auth0）を使ったログインフローをResourceで実装したい。

## 例

### AuthInterface

認証backendの抽象。providerはDI bindingで差し替える:

```php
interface AuthInterface
{
    /** Build the OAuth provider's authorisation URL (where to redirect the user). */
    public function getAuthorizationUrl(string|null $state = null): string;

    /** Exchange the authorisation code for an authenticated user. */
    public function authenticate(string $code, string $state): AuthenticatedUser;
}
```

### Resource

GETがauthorization URLを返し、POSTがcode+stateをtoken exchangeする2段階フロー。失敗は `Throwable` を握って固定文言の401:

```php
class Auth extends ResourceObject
{
    public function __construct(
        private readonly AuthInterface $auth,
    ) {
    }

    #[JsonSchema('auth_authorization.json')]
    public function onGet(): static
    {
        $this->body = [
            'authorizationUrl' => $this->auth->getAuthorizationUrl(),
        ];

        return $this;
    }

    #[JsonSchema(schema: 'auth_response.json', params: 'auth_exchange.json')]
    public function onPost(#[Input] AuthExchangeInput $input): static
    {
        try {
            $user = $this->auth->authenticate($input->code, $input->state);
        } catch (Throwable) {
            $this->code = Code::UNAUTHORIZED;
            $this->body = ['message' => 'Authentication failed'];

            return $this;
        }

        $this->body = [
            'id' => $user->id,
            'provider' => $user->provider,
            'subject' => $user->subject,
            'email' => $user->email,
            'name' => $user->name,
        ];

        return $this;
    }
}
```

### Input DTO

`code` + `state` を運ぶだけの `final readonly` DTO（→ [`api-post-input-dto`](./api-post-input-dto.md)）:

```php
final readonly class AuthExchangeInput
{
    public function __construct(
        #[Input]
        public string $code,
        #[Input]
        public string $state,
    ) {
    }
}
```

### Provider実装

league/oauth2-client を包み、token exchange の結果を `AuthenticatedUser` に正規化する:

```php
final class GoogleAuthProvider implements AuthInterface
{
    public function __construct(
        private readonly Google $provider,
    ) {
    }

    public function getAuthorizationUrl(string|null $state = null): string
    {
        $options = [
            'scope' => ['openid', 'email', 'profile'],
        ];
        if ($state !== null) {
            $options['state'] = $state;
        }

        return $this->provider->getAuthorizationUrl($options);
    }

    public function authenticate(string $code, string $state): AuthenticatedUser
    {
        $token = $this->provider->getAccessToken('authorization_code', ['code' => $code]);
        $user = $this->provider->getResourceOwner($token);
        if (! $user instanceof GoogleUser) {
            throw new UnexpectedAuthProviderResponseException('Unexpected resource owner type from Google.');
        }

        return new AuthenticatedUser(
            id: (string) $user->getId(),
            email: (string) $user->getEmail(),
            name: (string) $user->getName(),
            provider: 'google',
            subject: (string) $user->getId(),
        );
    }
}
```

### Binding切替

prodは env、test/fakeは module — 2層で切り替える:

```php
// AppModule — CMS_AUTH_PROVIDER で Google / Auth0 を選択（不正値は例外）
$authProvider = strtolower(trim((string) getenv('CMS_AUTH_PROVIDER')));
if (! in_array($authProvider, ['', 'google', 'auth0'], true)) {
    throw new InvalidAuthProviderException($authProvider);
}

$this->bind(AuthInterface::class)->to(
    $authProvider === 'auth0' ? Auth0AuthProvider::class : GoogleAuthProvider::class,
)->in(Scope::SINGLETON);
```

```php
// FakeModule — 決定的な FakeAuthProvider へ
$this->bind(AuthInterface::class)->to(FakeAuthProvider::class)->in(Scope::SINGLETON);
```

## Naming

認証まわりの命名:

| 種別 | 形 | 例 |
|---|---|---|
| 抽象 | `AuthInterface` | `getAuthorizationUrl()` / `authenticate()` |
| 実装 | `<Provider>AuthProvider` | `GoogleAuthProvider`, `Auth0AuthProvider` |
| テスト代替 | `Fake<Name>` | `FakeAuthProvider`（`tests/Fake/`） |
| Input DTO | `<動作>Input` | `AuthExchangeInput`（`src/Input/`） |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 認証backendを `AuthInterface` で抽象化し、providerをDI bindingで切り替えられるようにしたか。
- [ ] GETでauthorization URLを返し、POSTでcode+stateをtoken exchangeする2段階フローにしたか。
- [ ] 認証失敗時はprovider内部情報を漏らさず401にすると決めたか。

## Source

- [`src/Resource/App/Auth.php`](../src/Resource/App/Auth.php)
- [`src/Auth/AuthInterface.php`](../src/Auth/AuthInterface.php)
- [`src/Auth/GoogleAuthProvider.php`](../src/Auth/GoogleAuthProvider.php)
- [`src/Auth/Auth0AuthProvider.php`](../src/Auth/Auth0AuthProvider.php)
- [`src/Input/AuthExchangeInput.php`](../src/Input/AuthExchangeInput.php)

## Tests

- [`tests/Resource/App/AuthTest.php`](../tests/Resource/App/AuthTest.php)
- [`tests/Fake/FakeAuthProvider.php`](../tests/Fake/FakeAuthProvider.php)
- [`tests/Smoke/GoogleAuthProviderSmokeTest.php`](../tests/Smoke/GoogleAuthProviderSmokeTest.php)

## Key points

`AuthInterface` でproviderを抽象化。GET→authorization URL、POST→token exchange。失敗は401でprovider内部を漏らさない。providerの切替は2層 — prodは `CMS_AUTH_PROVIDER` envでGoogle/Auth0を選択（不正値は `InvalidAuthProviderException`）、test/fakeは `FakeModule` が `FakeAuthProvider` へbind。session確立まで含むPage側のフローは [`admin-session-login`](./admin-session-login.md) を参照。

## Do not

- provider固有の例外や内部メッセージをresponse bodyに含めない — token introspection結果やprovider側のエラー文字列が漏れる。`catch (Throwable)` で固定文言の401に落とし、詳細はlogger側に送る。

## マスター確認（After）

- [ ] `AuthInterface` binding が test と prod で切り替わる（FakeAuthProvider vs GoogleAuthProvider）。
- [ ] GET で authorizationUrl が返り、POST で authenticated user が返ることを `AuthTest.php` 相当で green。

## See also

- [`admin-session-login`](./admin-session-login.md) — code+state交換からsession確立まで含むPage側のログインフロー
- [`admin-auth-boundary`](./admin-auth-boundary.md) — 認証後のuserを型付き `UserInterface` / `AdminUserInterface` 境界で扱う
- [`api-post-input-dto`](./api-post-input-dto.md) — POST入力を `#[Input]` DTOで受ける型
- [`json-schema-validation`](./json-schema-validation.md) — `#[JsonSchema]` によるrequest/response検証
- [`resource-permission-authorization`](./resource-permission-authorization.md) — 認証の先、Resource単位の認可
- [`error-status-mapping`](./error-status-mapping.md) — 例外→statusコードの対応付け
