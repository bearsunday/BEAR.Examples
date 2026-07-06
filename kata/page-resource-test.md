# `page-resource-test`

**Page ResourceのHTML contractをテストする** · [← 索引に戻る](../index.md)

- **Category:** Tests / fake
- **Status:** `support`
- **Aliases:** page test, Qiq test, HTML resource test, html-test-hal-api-app, HTMLテスト, ページテスト, XSS回帰
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** Page Resourceとtemplateが期待するHTMLやstatusを固定したい。

## 例

### TestCase基底

context `html-test-hal-api-app` が `HtmlModule` とFakeを合成する — Page testはDBなしでHTML描画まで検証できる。既定ユーザーは `Visitor`:

```php
abstract class AbstractPageTestCase extends TestCase
{
    protected ResourceInterface $resource;

    protected function setUp(): void
    {
        $injector = Injector::getOverrideInstance('html-test-hal-api-app', new FakeUserModule(new Visitor()));
        $this->resource = $injector->getInstance(ResourceInterface::class);
    }

    protected function resourceWithUser(UserInterface $user): ResourceInterface
    {
        $injector = Injector::getOverrideInstance('html-test-hal-api-app', new FakeUserModule($user));

        return $injector->getInstance(ResourceInterface::class);
    }
}
```

### Fake admin session（admin test）

admin testは fake admin session を明示注入した基底を使う:

```php
abstract class AbstractAdminPageTestCase extends AbstractPageTestCase
{
    protected function setUp(): void
    {
        $this->resource = $this->resourceWithUser(new AdminUser(
            id: 'test-admin-1',
            email: 'evelyn.moore1@example.com',
            name: 'Evelyn Moore',
            authorId: 1,
        ));
    }
}
```

`FakeUserModule` は `AuthSessionInterface` をfakeに差し替えるだけの小さなmodule:

```php
final class FakeUserModule extends AbstractModule
{
    public function __construct(
        private readonly UserInterface $user,
        AbstractModule|null $module = null,
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $this->bind(AuthSessionInterface::class)->toInstance(new FakeAuthSession($this->user));
    }
}
```

### HTML contract

描画は `$ro->toString()` で行い、`$ro->view` と同一であることを pin する:

```php
public function testOnGetReturnsHtml(): void
{
    $ro = $this->resource->get('page://self/article', ['id' => 1]);

    $this->assertSame(200, $ro->code);

    $html = $ro->toString();
    $this->assertSame($html, $ro->view);
}
```

### XSS回帰

escapingはfake dataの `xss-regression` fixture（id=51）で回帰テストする:

```php
public function testArticleFieldsAreEscaped(): void
{
    $ro = $this->resource->get('page://self/article', ['id' => 51]);
    $html = $ro->toString();

    $this->assertStringNotContainsString('<script>alert("xss")</script>Bad', $html);
    $this->assertStringContainsString('&lt;script&gt;', $html);
}
```

### 404もcontract

statusだけでなくError templateの描画内容まで pin する:

```php
public function testNotFoundReturns404(): void
{
    $ro = $this->resource->get('page://self/article', ['id' => 99999]);

    $this->assertSame(404, $ro->code);
}

public function testNotFoundRendersErrorTemplate(): void
{
    $ro = $this->resource->get('page://self/article', ['id' => 99999]);
    $html = $ro->toString();

    $this->assertStringContainsString('<h1>Error 404</h1>', $html);
    $this->assertStringContainsString('An unexpected error occurred.', $html);
    $this->assertStringNotContainsString('<article class="Article">', $html);
}
```

## Naming

| 対象 | 規則 | 例 |
|---|---|---|
| Page test | `tests/Resource/Page/<Name>Test.php` — Page Resourceと同名 | `ArticleTest`, `IndexTest` |
| 公開ページの基底 | `AbstractPageTestCase`（既定 `Visitor`） | `ArticleTest extends AbstractPageTestCase` |
| adminページの基底 | `AbstractAdminPageTestCase`（fake admin session注入済み） | `Admin/ArticleTest extends AbstractAdminPageTestCase` |
| Fake | `tests/Fake/Fake<Name>.php` | `FakeUserModule`, `FakeAuthSession` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] HTML context（`HtmlModule` + Fake）でDBなしに描画を検証すると決めたか。
- [ ] 既定は `Visitor`、admin testでは fake admin session を明示注入すると理解したか。

## Source

- [`tests/AbstractPageTestCase.php`](../tests/AbstractPageTestCase.php)
- [`tests/AbstractAdminPageTestCase.php`](../tests/AbstractAdminPageTestCase.php)
- [`tests/Fake/FakeUserModule.php`](../tests/Fake/FakeUserModule.php)
- [`tests/Resource/Page/ArticleTest.php`](../tests/Resource/Page/ArticleTest.php)
- [`tests/Resource/Page/ArticleListTest.php`](../tests/Resource/Page/ArticleListTest.php)

## Tests

- [`tests/Resource/Page/ArticleTest.php`](../tests/Resource/Page/ArticleTest.php)
- [`tests/Resource/Page/IndexTest.php`](../tests/Resource/Page/IndexTest.php)

## Key points

HTML contextは `HtmlModule` とFakeを合成してDBなしで描画を検証する。描画は `$ro->toString()` で行い `$ro->view` と同一であることを pin。escapingはfake dataの `xss-regression` fixture（id=51）で回帰テストする。404もcontract — statusだけでなくError templateの描画内容まで pin する。

## マスター確認（After）

- [ ] Page test がDBなしで green。
- [ ] HTML出力の要素・status を pin し `Resource/Page/ArticleTest.php` 相当が green。

## See also

- [`app-resource-test`](./app-resource-test.md) — App API側のResourceテスト（HAL bodyとstatusを pin する相手方）
- [`fake-sql-query`](./fake-sql-query.md) — DBなしテストを支えるFakeSqlQuery
- [`hypermedia-workflow-test`](./hypermedia-workflow-test.md) — 単一endpointではなくrel遷移の連なりを検証するテスト
- [`page-resource-qiq-detail`](./page-resource-qiq-detail.md) — テスト対象となる詳細PageのHTML描画の型
- [`page-resource-list`](./page-resource-list.md) — テスト対象となる一覧Pageの型
- [`admin-auth-boundary`](./admin-auth-boundary.md) — `Visitor` / `AdminUser` の型境界（fake admin sessionが越える境界）
- [`semantic-fake-data`](./semantic-fake-data.md) — `xss-regression` fixtureを含む決定的fake dataの生成
