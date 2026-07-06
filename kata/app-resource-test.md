# `app-resource-test`

**App ResourceのAPI contractをテストする** · [← 索引に戻る](../index.md)

- **Category:** Tests / fake
- **Status:** `support`
- **Aliases:** resource test, API test, HAL JSON test, test-hal-api-app, ResourceInterface, リソーステスト, APIテスト, 契約テスト
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** App Resourceのstatus code、body shape、write flowを固定したい。

## 例

### 共通setUp

context `test-hal-api-app` で `ResourceInterface` を取得する — FakeModule が入り、実DBに触れない:

```php
abstract class AbstractAppTestCase extends TestCase
{
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getInstance('test-hal-api-app');
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }
}
```

### GET contract

client視点でResourceを呼び、status・body・hypermedia を pin する。`_embedded` / `_links` はrendered JSONで確認する:

```php
public function testOnGetReturnsEmbeddedAuthorCategoryTags(): void
{
    $ro = $this->resource->get('app://self/article', ['id' => 1]);

    $this->assertSame(200, $ro->code);
    $this->assertSame(1, $ro->body['id']);

    // The HAL renderer materialises #[Embed] requests under _embedded.
    $rendered = json_decode((string) $ro, true);
    $this->assertSame($ro->body['authorId'], $rendered['_embedded']['author']['id']);
    $this->assertArrayHasKey('goArticleList', $rendered['_links']);
}
```

collection側も同様に body shape を pin する（`ArticlesTest`）:

```php
$ro = $this->resource->get('app://self/articles', ['perPage' => 9999]);
$this->assertSame(100, $ro->body['perPage']);
```

### Write flow

write後の副作用は再GETで確認する — PUT後のbody変化、DELETE後の404:

```php
$post = $this->resource->post('app://self/article', [
    'slug' => 'test-article-' . uniqid(),
    'title' => 'Test Article',
    'body' => 'Body content.',
    'authorId' => 1,
    'categoryId' => 1,
    'status' => 'draft',
]);
$this->assertSame(201, $post->code);

$id = $post->body['id'];
$put = $this->resource->put('app://self/article', [
    'id' => $id,
    'title' => 'Updated Title',
    'body' => 'Updated body.',
    'status' => 'published',
]);
$this->assertSame(200, $put->code);

$getAfter = $this->resource->get('app://self/article', ['id' => $id]);
$this->assertSame('Updated Title', $getAfter->body['title']);

$delete = $this->resource->delete('app://self/article', ['id' => $id]);
$this->assertSame(204, $delete->code);

$getMissing = $this->resource->get('app://self/article', ['id' => $id]);
$this->assertSame(404, $getMissing->code);
```

### 例外経路

必須field欠落は `ParameterException`:

```php
$this->expectException(ParameterException::class);
$this->resource->post('app://self/article', [
    'slug' => 'valid-slug',
    'title' => 'T',
    // body, authorId, categoryId all missing
]);
```

schema違反は `ValidationException` — メッセージまでassertする:

```php
try {
    $this->resource->post('app://self/article', [
        'slug' => 'INVALID Slug With Spaces',
        'title' => 'Title',
        'body' => 'Body',
        'authorId' => 1,
        'categoryId' => 1,
        'status' => 'draft',
    ]);
    $this->fail('Expected ValidationException');
} catch (ValidationException $e) {
    $errors = $e->errors;
    $this->assertArrayHasKey('slug', $errors);
    $this->assertSame(
        'Slug must contain only lowercase letters, digits and hyphens.',
        $errors['slug'][0],
    );
}
```

### Binding差し替え

テスト個別のbinding差し替えは `Injector::getOverrideInstance()`（`ResourceSmokeTest` のsetUp）:

```php
protected function setUp(): void
{
    parent::setUp();

    $injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
    $this->cacheResource = $injector->getInstance(ResourceInterface::class);
}
```

## Naming

テストclassは対象Resourceの相対pathをミラーし `<Resource>Test` と命名する（PSR-4 は `src/` と `tests/` の両方に載る）:

| 対象 | テスト |
|---|---|
| `src/Resource/App/Article.php` | `tests/Resource/App/ArticleTest.php` |
| `src/Resource/App/Articles.php` | `tests/Resource/App/ArticlesTest.php` |

共通setUpは `tests/AbstractAppTestCase.php`。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] client視点でResourceを呼び、status / body / schema / hypermedia の期待を pin すると決めたか。
- [ ] private method単位ではなく Resource の contract をテスト対象にすると理解したか。
- [ ] テスト個別のbinding差し替えは `Injector::getOverrideInstance($context, $module)` で行うと理解したか。

## Source

- [`tests/AbstractAppTestCase.php`](../tests/AbstractAppTestCase.php)
- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Resource/App/ArticlesTest.php`](../tests/Resource/App/ArticlesTest.php)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Smoke/ResourceSmokeTest.php`](../tests/Smoke/ResourceSmokeTest.php)

## Key points

client視点でResourceを呼び、status/body/schema/hypermediaの期待を pin する。例外経路もcontract — 必須field欠落（`ParameterException`）とschema違反（`ValidationException`）をメッセージまでassertする。`ResourceSmokeTest` は全GET resourceをfake引数で叩き、宣言schemaに対してrepresentationを検証する網羅smoke。

## Do not

- 実装内部のprivate method単位を主テストにしない — テスト対象は `ResourceInterface` 経由のcontract（status / body / schema / hypermedia）であって、Resource classの内部実装ではない。

## マスター確認（After）

- [ ] テストが `ResourceInterface` 経由でResourceを呼んでいる（内部privateを直接叩いていない）。
- [ ] status code と body shape を pin し green。
- [ ] write後の副作用を再GETで確認している（PUT後のbody変化、DELETE後の404）。

## See also

- [`fake-sql-query`](./fake-sql-query.md) — `test-hal-api-app` が使うDB不要のfake基盤
- [`page-resource-test`](./page-resource-test.md) — HTML Page側のcontractテスト
- [`hypermedia-workflow-test`](./hypermedia-workflow-test.md) — Link/Embedを辿るworkflowのテスト
- [`mysql-integration-test`](./mysql-integration-test.md) — 実DB経路を必要時だけ検証する統合テスト
- [`json-schema-validation`](./json-schema-validation.md) — `ValidationException` を投げるschema検証の実装側
- [`api-post-input-dto`](./api-post-input-dto.md) — `ParameterException` の発生源となるInput DTO
