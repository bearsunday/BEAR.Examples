# `csrf-same-origin-protection`

**CSRFトークン + Same-Origin interceptorをAOPでbindする** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `canonical`
- **Aliases:** CSRF, CsrfToken, SameOrigin, interceptor, AOP, form protection, synchronizer token, シンクロナイザートークン, CSRF対策, Ray.Csrf, _csrf_token, Sec-Fetch-Site
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Use when:** Admin Page Resourceのwrite操作をCSRF攻撃とCross-Site Origin攻撃から保護したい。

## 例

### Attribute

中身を持たないmarker attribute — AOP matcherの目印:

```php
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class CsrfToken
{
}

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SameOrigin
{
}
```

Adminのwrite methodに両方並べて付ける:

```php
#[SameOrigin]
#[CsrfToken]
public function onPost(): static
```

### CsrfModule — AOP bind

attributeの付いた `ResourceObject` methodへ、それぞれのinterceptorをbindする:

```php
final class CsrfModule extends AbstractModule
{
    public function __construct(
        private readonly string|null $allowedOrigin = null,
        private readonly string $csrfTokenField = '_csrf_token',
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->bind(AllowedOrigin::class)->toInstance(new AllowedOrigin($this->allowedOrigin));

        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(SameOrigin::class),
            [SameOriginInterceptor::class],
        );

        $this->bind(CsrfTokenInterface::class)->to(SessionCsrfToken::class)->in(Scope::SINGLETON);
        $this->bindInterceptor(
            $this->matcher->subclassesOf(ResourceObject::class),
            $this->matcher->annotatedWith(CsrfToken::class),
            [CsrfTokenInterceptor::class],
        );
    }
}
```

install側（`AppModule`）— env未設定なら `null` で両interceptorとも素通し（dev / CLI）:

```php
$allowedOrigin = (string) getenv('CMS_ALLOWED_ORIGIN') ?: null;
$this->install(new CsrfModule($allowedOrigin));
```

### CsrfTokenInterceptor — synchronizer token検証

request bodyのtokenをsession保存のserver側stateと突き合わせる。欠落もmismatchも `ForbiddenException`:

```php
public function invoke(MethodInvocation $invocation): mixed
{
    if ($this->allowedOrigin->value === null) {
        return $invocation->proceed();
    }

    $submitted = $this->body->submitted();
    if ($submitted === null) {
        throw new ForbiddenException('CSRF token missing.');
    }

    if (! $this->csrf->verify($submitted)) {
        throw new ForbiddenException('CSRF token invalid.');
    }

    return $invocation->proceed();
}
```

### SessionCsrfToken

server側stateは `$_SESSION` に保存（cookieは使わない）。比較は `hash_equals`:

```php
public function issue(): string
{
    $this->start();

    $existing = $_SESSION[self::SESSION_KEY] ?? null;
    if (is_string($existing) && $existing !== '') {
        return $existing;
    }

    $token = bin2hex(random_bytes(32));
    $_SESSION[self::SESSION_KEY] = $token;

    return $token;
}

public function verify(string $candidate): bool
{
    $this->start();

    $stored = $_SESSION[self::SESSION_KEY] ?? null;
    if (! is_string($stored) || $stored === '' || $candidate === '') {
        return false;
    }

    return hash_equals($stored, $candidate);
}
```

### SameOriginInterceptor — 3シグナル判定

優先順: `Sec-Fetch-Site` があればそれだけで判定し、無ければ `Origin` → `Referer` の順:

```php
$fetchSite = $this->request->fetchSite();
if ($fetchSite !== null) {
    return $this->checkFetchSite($invocation, $fetchSite);
}

return $this->checkOriginOrReferer($invocation, $allowedCanonical);
```

fail-closed — 未知の `Sec-Fetch-Site` 値もOrigin/Refererへfallbackせず拒否、シグナル全欠落も拒否:

```php
throw new ForbiddenException(
    sprintf('Same-origin policy: unknown Sec-Fetch-Site value: %s.', $fetchSite),
);
```

```php
throw new ForbiddenException(
    'Same-origin policy: no Sec-Fetch-Site / Origin / Referer header.',
);
```

malformedとmismatchは区別する — parse不能な `Origin`/`Referer` は400、origin不一致は403:

```php
$originCanonical = $this->canonicaliseOrigin($origin);
if ($originCanonical === null) {
    throw new BadRequestException(
        sprintf('Same-origin policy: malformed Origin header: %s.', $origin),
    );
}

if ($originCanonical === $allowedCanonical) {
    return $invocation->proceed();
}

throw new ForbiddenException(
    sprintf('Same-origin policy: cross-origin Origin: %s.', $origin),
);
```

## Naming

attribute → interceptor → module の対応が名前で追える:

| 役割 | 命名 | 例 |
|---|---|---|
| Marker attribute | `<Concern>`（`TARGET_METHOD`） | `CsrfToken`, `SameOrigin` |
| Interceptor | `<Attribute名>Interceptor` | `CsrfTokenInterceptor`, `SameOriginInterceptor` |
| 束ねるModule | `<Concern>Module` | `CsrfModule` |
| hidden field名 | `_csrf_token`（`CsrfModule` 第2引数の既定値） | template変数 `$csrfTokenField` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[CsrfToken]` と `#[SameOrigin]` の2つのAttributeを使い、それぞれ interceptor をAOP bindすると決めたか。
- [ ] CSRFトークンはsynchronizer token方式（sessionに保存したserver側stateと `hash_equals` で比較。cookieは使わない）と理解したか。
- [ ] Same-Originは `Sec-Fetch-Site` / `Origin` / `Referer` の3シグナルで判定し、全欠落時はfail-closedにすると理解したか。
- [ ] `CsrfModule(allowedOrigin)` の `AllowedOrigin` が `null` なら両interceptorとも素通し（dev/CLI/test用スイッチ）で、prodでは `CMS_ALLOWED_ORIGIN` の設定が必要と理解したか。

## Source

- [`src-csrf/Attribute/CsrfToken.php`](../src-csrf/Attribute/CsrfToken.php)
- [`src-csrf/Attribute/SameOrigin.php`](../src-csrf/Attribute/SameOrigin.php)
- [`src-csrf/Interceptor/CsrfTokenInterceptor.php`](../src-csrf/Interceptor/CsrfTokenInterceptor.php)
- [`src-csrf/Interceptor/SameOriginInterceptor.php`](../src-csrf/Interceptor/SameOriginInterceptor.php)
- [`src-csrf/CsrfModule.php`](../src-csrf/CsrfModule.php)
- [`src-csrf/SessionCsrfToken.php`](../src-csrf/SessionCsrfToken.php)
- [`src/Module/AppModule.php`](../src/Module/AppModule.php)

## Tests

- [`tests/Interceptor/CsrfTokenInterceptorTest.php`](../tests/Interceptor/CsrfTokenInterceptorTest.php)
- [`tests/Interceptor/CsrfTokenWiringTest.php`](../tests/Interceptor/CsrfTokenWiringTest.php)
- [`tests/Interceptor/SameOriginInterceptorTest.php`](../tests/Interceptor/SameOriginInterceptorTest.php)
- [`tests/Interceptor/SameOriginWiringTest.php`](../tests/Interceptor/SameOriginWiringTest.php)
- [`tests/Interceptor/AdminPageCsrfAttributeCoverageTest.php`](../tests/Interceptor/AdminPageCsrfAttributeCoverageTest.php)

## Key points

`#[CsrfToken]` → synchronizer token検証（`$_SESSION` 保存 + `hash_equals`）。`#[SameOrigin]` → Sec-Fetch-Site/Origin/Referer 3シグナル判定、fail-closed（未知の `Sec-Fetch-Site` 値もfallbackしない）。malformedな `Origin`/`Referer` は400、mismatchは403。hidden fieldはrendererが全templateへ供給する `$csrfTokenField` で埋め、logout時は `CsrfTokenInterface::clear()`。`AdminPageCsrfAttributeCoverageTest` が全Admin write methodへの付け忘れをreflectionで検出する。

## Do not

- prodで `CMS_ALLOWED_ORIGIN` 未設定のまま公開しない — `AllowedOrigin` が `null` だと両interceptorとも素通し（dev/CLI/test用スイッチ）になる。エラーにはならず、静かに無防備になる。

## マスター確認（After）

- [ ] token mismatch で `ForbiddenException` が throw される。
- [ ] origin mismatch で `ForbiddenException` が throw される。
- [ ] 全Admin write methodにattributeが付いていることを coverage test 相当で green。
- [ ] `CsrfTokenInterceptorTest.php` / `SameOriginInterceptorTest.php` 相当で green。

## See also

- [`admin-prg-form`](./admin-prg-form.md) — この2 attributeが守るAdmin form POST（PRG）
- [`admin-confirm-page`](./admin-confirm-page.md) — 確認画面のPOSTも同じ2 attributeで保護する
- [`admin-auth-boundary`](./admin-auth-boundary.md) — 同じAdmin Pageを守るもう一つの境界（認証401 / 認可403）
- [`admin-session-login`](./admin-session-login.md) — tokenを保持するsessionの確立とlogout
- [`aop-validation-valid`](./aop-validation-valid.md) — attribute + interceptorでmethodにgateを掛ける同型（validation）
- [`rate-limit-interceptor`](./rate-limit-interceptor.md) — attribute + interceptorでmethodにgateを掛ける同型（rate limit）
