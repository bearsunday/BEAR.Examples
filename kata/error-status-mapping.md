# `error-status-mapping`

**例外→HTTPステータスマッピングとエラーハンドリング** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** error handling, exception handler, status mapping, error page, JsonSchemaRequestExceptionHandler, AppThrowableHandler, 例外処理, エラーハンドリング, ステータスマッピング, 例外→HTTP
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** 例外をHTTPステータスコードにマッピングし、カスタムエラーページとJSON Schema検証例外ハンドリングを提供したい。

## 例

### ExceptionStatusMapper

ドメイン例外→HTTPステータスの対応表。未知のthrowableには `null` を返す（→ handlerがframeworkにdelegate）:

```php
public function status(Throwable $e): int|null
{
    if ($e instanceof ValidationException || $e instanceof JsonSchemaRequestException) {
        return Code::BAD_REQUEST;
    }

    if ($e instanceof UnauthenticatedException) {
        return Code::UNAUTHORIZED;
    }

    if ($e instanceof ForbiddenException) {
        return Code::FORBIDDEN;
    }

    if (
        $e instanceof ArticleNotFoundException
        || $e instanceof AuthorNotFoundException
        || $e instanceof CategoryNotFoundException
        || $e instanceof TagNotFoundException
    ) {
        return Code::NOT_FOUND;
    }

    return null;
}
```

`message()` / `errors()` も同じmapperが持つ — JSONとHTMLの両handlerがステータスと文言で一致するのはこの共有のおかげ。

### AppThrowableHandler

mapperが返したstatusでエラーページを組み立てる。`null` ならframeworkの `ErrorInterface` にfallback:

```php
#[Override]
public function handle(Throwable $e, Request $request): self
{
    $status = $this->mapper->status($e);
    if ($status === null) {
        $this->delegated = true;
        $this->fallback->handle($this->asException($e), $request);

        return $this;
    }

    $this->delegated = false;
    $body = ['message' => $this->mapper->message($e, $status)];
    $errors = $this->mapper->errors($e);
    if ($errors !== []) {
        $body['errors'] = $errors;
    }

    $this->errorPage = new AppErrorPage($status, $body);

    return $this;
}
```

HTML contextの `HtmlThrowableHandler` は同じ形で、`AppErrorPage` の代わりに `HtmlErrorPage` を描画する。

### AppErrorPage

エラー表現も `ResourceObject`:

```php
final class AppErrorPage extends ResourceObject
{
    /** @param array<string, mixed> $body */
    public function __construct(int $code, array $body)
    {
        $this->code = $code;
        $this->headers = ['Content-Type' => 'application/json; charset=utf-8'];
        $this->body = ['code' => $code] + $body;
    }
}
```

### AppErrorModule

`ThrowableHandlerInterface` を束縛する。HTML contextでは `HtmlModule` が `HtmlThrowableHandler` に差し替える:

```php
final class AppErrorModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(ExceptionStatusMapper::class);
        $this->bind(ThrowableHandlerInterface::class)->to(AppThrowableHandler::class);
    }
}
```

### JsonSchemaRequestExceptionHandler

request schema違反を `field => list<string>` の `ValidationException` に変換して投げる（throwが `proceed()` を止める唯一の手段）:

```php
#[Override]
public function handleRequestException(
    array $arguments,
    ResourceObject $ro,
    JsonSchemaException $e,
    string $schemaFile,
): never {
    $errors = $e->getErrors();
    if (! $errors->hasErrors()) {
        throw $e;
    }

    $collected = [];
    foreach ($errors as $error) {
        $field = $error->property === '' ? '_root' : $error->property;
        $messages = $collected[$field] ?? [];
        $messages[] = $error->message;
        $collected[$field] = $messages;
    }

    throw new ValidationException($collected, $e);
}
```

## Naming

例外・handler・エラーページの命名:

| 種別 | 命名 | 例 |
|---|---|---|
| ドメイン例外 | `BEAR\Kata\Exception\<DomainName>Exception` | `ArticleNotFoundException`, `ForbiddenException` |
| Throwable handler | `<Context>ThrowableHandler` | `AppThrowableHandler`（JSON）, `HtmlThrowableHandler`（HTML） |
| エラーページ | `<Context>ErrorPage` | `AppErrorPage`, `HtmlErrorPage` |

汎用の `LogicException` / `RuntimeException` は使わない — `src/` 起源のthrowは必ずドメイン例外を定義する。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] ドメイン例外を `ExceptionStatusMapper` でHTTPステータスにマッピングすると決めたか。
- [ ] APIとHTMLで別のハンドラ（`AppThrowableHandler` / `HtmlThrowableHandler`）を使うと理解したか。
- [ ] JSON Schema validationエラーを `JsonSchemaRequestExceptionHandler` で `ValidationException` に変換すると決めたか。

## Source

- [`src/Provide/Error/ExceptionStatusMapper.php`](../src/Provide/Error/ExceptionStatusMapper.php)
- [`src/Provide/Error/AppThrowableHandler.php`](../src/Provide/Error/AppThrowableHandler.php)
- [`src/Provide/Error/HtmlThrowableHandler.php`](../src/Provide/Error/HtmlThrowableHandler.php)
- [`src/Provide/Error/AppErrorPage.php`](../src/Provide/Error/AppErrorPage.php)
- [`src/Module/AppErrorModule.php`](../src/Module/AppErrorModule.php)
- [`src/Validation/JsonSchemaRequestExceptionHandler.php`](../src/Validation/JsonSchemaRequestExceptionHandler.php)

## Tests

- [`tests/Provide/Error/ExceptionStatusMapperTest.php`](../tests/Provide/Error/ExceptionStatusMapperTest.php)
- [`tests/Provide/Error/ThrowableHandlerTest.php`](../tests/Provide/Error/ThrowableHandlerTest.php)
- [`tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`](../tests/Validation/JsonSchemaRequestExceptionHandlerTest.php)

## Key points

`ExceptionStatusMapper` でドメイン例外→HTTPステータス。APIは `AppThrowableHandler`、HTMLは `HtmlThrowableHandler`（`HtmlModule` が差し替え）。mapperが `null` を返す未知のthrowableはframeworkの `ErrorInterface` にdelegateする。`JsonSchemaResponseException`（response schema違反=server bug）は意図的にunmappedのまま500。公式manualも本構成（AppThrowableHandler / HtmlThrowableHandler / 共有ExceptionStatusMapper）をJSON vs HTML contextの参照実装として挙げている。

## Do not

- `JsonSchemaRequestException` と `JsonSchemaResponseException` をまとめて400にマッピングしない — request側はclientの入力ミスだが、response側はserver bugであり、unmappedのまま500でframeworkにdelegateするのが正しい。

## マスター確認（After）

- [ ] 各ドメイン例外が正しいステータスコードにマッピングされることを `ExceptionStatusMapperTest.php` 相当で green。
- [ ] response schema違反がclient errorに化けず500のままであることを pin。

## See also

- [`json-schema-validation`](./json-schema-validation.md) — `#[JsonSchema]` によるrequest/response検証（変換元の例外を投げる側）
- [`aop-validation-valid`](./aop-validation-valid.md) — アプリケーションバリデーションが `ValidationException` を投げる形
- [`form-validation-webform`](./form-validation-webform.md) — Page層が `ValidationException` をcatchして422フォーム再描画する側
- [`not-found-response`](./not-found-response.md) — Readの404はthrowせず `$this->code` で返す（例外マッピングとの使い分け）
- [`resource-permission-authorization`](./resource-permission-authorization.md) — `UnauthenticatedException` / `ForbiddenException` を投げる認可境界
