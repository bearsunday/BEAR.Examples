# `admin-auth-boundary`

**型で表現する認証境界とauthor-scoped認可** · [← 索引に戻る](../index.md)

- **Category:** HTML / Page
- **Status:** `showcase`
- **Aliases:** AdminGuard, auth boundary, author-scoped, authorization, session identity, admin page protection, Visitor, AdminUser, UnauthenticatedException, 認可, 認証境界, 401, 403
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** Admin Page Resourceで、ログイン済みユーザーが自分の記事のみ操作できる認可境界を設けたい。

## 例

### 型の階層

現在ユーザーは常に `UserInterface` — 匿名なら `Visitor`、ログイン済みadminなら `AdminUserInterface`:

```php
interface UserInterface
{
}

final readonly class Visitor implements UserInterface
{
}

interface AdminUserInterface extends UserInterface
{
    public function authorId(): int;
}
```

### AdminGuard

`UserInterface` を注入し、`instanceof` で `AdminUserInterface` へ絞り込む。失敗は `UnauthenticatedException`（401）:

```php
final readonly class AdminGuard
{
    public function __construct(
        private UserInterface $user,
    ) {
    }

    public function user(): AdminUserInterface
    {
        if (! $this->user instanceof AdminUserInterface) {
            throw new UnauthenticatedException();
        }

        return $this->user;
    }
}
```

### Provider

sessionが返す現在ユーザーを `UserInterface` としてbindする:

```php
/** @implements ProviderInterface<UserInterface> */
final readonly class CurrentUserProvider implements ProviderInterface
{
    public function __construct(
        private AuthSessionInterface $session,
    ) {
    }

    public function get(): UserInterface
    {
        return $this->session->currentUser();
    }
}
```

`AdminUserProvider` は同じ `instanceof` 絞り込みをDI層でも提供する — `AdminUserInterface` を直接注入したい箇所はこのProvider経由で受ける。

### Resource — author-scoped認可

guardが担うのは「admin型への絞り込み」まで。ownership判定は各Admin Resourceの `owns()` が担い、他authorの記事は403:

```php
public function onGet(int|null $id = null, string|null $saved = null): static
{
    $admin = $this->admin->user();
    $article = $id === null ? null : $this->article->item($id);
    // ...
    if ($article !== null && ! $this->owns($article, $admin)) {
        return $this->forbidden();
    }
    // ...
}

private function owns(ArticleEntity $article, AdminUserInterface $admin): bool
{
    return $article->authorId === $admin->authorId();
}

private function forbidden(): static
{
    $this->code = Code::FORBIDDEN;
    $this->body = ['message' => 'Forbidden'];

    return $this;
}
```

## Naming

認証境界の型ボキャブラリ:

| 役割 | 型 | 例 |
|---|---|---|
| 現在ユーザー（常にこの型で受ける） | `UserInterface` | `CurrentUserProvider` がbind |
| 匿名ユーザー | `UserInterface` 実装 | `Visitor` |
| 管理者 | `AdminUserInterface`（`UserInterface` をextends） | `authorId(): int` を持つ |
| 絞り込み | `<Role>Guard` | `AdminGuard` |
| Provider | `src/Provider/<X>Provider.php` | `CurrentUserProvider`, `AdminUserProvider` |

例外は `BEAR\Kata\Exception\<DomainName>Exception` の形 — ここでは `UnauthenticatedException`。ownership判定のヘルパは `owns()`。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 認証境界を型で表現すると決めたか — sessionが返す `UserInterface` は `Visitor` か `AdminUser`。`AdminGuard` は `UserInterface` を注入し `instanceof AdminUserInterface` で絞り込む（失敗は `UnauthenticatedException` → 401）。
- [ ] author-scoped認可（作者本人以外の操作）は各Admin Resourceの `owns()` で `authorId` を比較し403にすると決めたか。

## Source

- [`src/Auth/AdminGuard.php`](../src/Auth/AdminGuard.php)
- [`src/Auth/AdminUserInterface.php`](../src/Auth/AdminUserInterface.php)
- [`src/Auth/UserInterface.php`](../src/Auth/UserInterface.php)
- [`src/Auth/Visitor.php`](../src/Auth/Visitor.php)
- [`src/Provider/AdminUserProvider.php`](../src/Provider/AdminUserProvider.php)
- [`src/Provider/CurrentUserProvider.php`](../src/Provider/CurrentUserProvider.php)
- [`src/Resource/Page/Admin/Article.php::owns()`](../src/Resource/Page/Admin/Article.php)

## Tests

- [`tests/Resource/Page/Admin/AuthBoundaryTest.php`](../tests/Resource/Page/Admin/AuthBoundaryTest.php)
- [`tests/Resource/Page/Admin/ArticleTest.php`](../tests/Resource/Page/Admin/ArticleTest.php)
- [`tests/Resource/Page/Admin/ArticleDeleteTest.php`](../tests/Resource/Page/Admin/ArticleDeleteTest.php)

## Key points

認証境界は型で表現する — `AdminGuard::user()` が `AdminUserInterface` へ絞り込み、非adminは `UnauthenticatedException`（`ExceptionStatusMapper` で401 → [`error-status-mapping`](./error-status-mapping.md)）。author-scoped認可は各Admin Resourceの `owns()`（`$article->authorId === $admin->authorId()`）が担い403を返す。guardに一元化されるのは「admin型への絞り込み」であり、ownership判定は意図的に各Resource本体に置く。

## Do not

- `AdminUserInterface` を直接bindしない — sessionは常に `UserInterface` を返し、admin型は `instanceof` 絞り込み（`AdminGuard` / `AdminUserProvider`）で受ける。直接bindするとVisitorが401になる経路が消え、認証（401）と認可（403）の区別が崩れる。

## マスター確認（After）

- [ ] Visitorがadminページに到達すると401になることを `AuthBoundaryTest.php` 相当で green。
- [ ] 他authorの記事のedit/update/deleteが403になることを `ArticleTest.php` / `ArticleDeleteTest.php` 相当で green。

## See also

- [`admin-session-login`](./admin-session-login.md) — sessionに `UserInterface` を確立するログインフロー（login → callback → logout）
- [`auth-oauth-flow`](./auth-oauth-flow.md) — OAuth認証フローのResource側
- [`error-status-mapping`](./error-status-mapping.md) — `UnauthenticatedException` → 401 の例外→ステータスマッピング
- [`admin-prg-form`](./admin-prg-form.md) — この境界の内側で動くAdmin form（PRG）
- [`admin-confirm-page`](./admin-confirm-page.md) — 確認画面Pageも同じguard + `owns()` の内側
- [`resource-permission-authorization`](./resource-permission-authorization.md) — `#[RequiredPermission]` によるリソース単位の認可の型
- [`page-resource-test`](./page-resource-test.md) — Page ResourceのHTML contractテスト（fake admin sessionの上書き）
