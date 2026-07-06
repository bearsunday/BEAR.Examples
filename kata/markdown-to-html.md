# `markdown-to-html`

**Markdown本文をHTMLへ変換する** · [← 索引に戻る](../index.md)

- **Category:** HTML / Page
- **Status:** `canonical`
- **Aliases:** Markdown, CommonMark, bodyHtml, renderer service, HTML conversion, League CommonMark, html_input ESCAPE, allow_unsafe_links, マークダウン
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/html.html
- **Use when:** EntityのMarkdown本文をHTML templateに渡す前に変換したい。

## 例

### Interface

変換はinterface越しに注入する — 呼び出し側はlibraryを知らない:

```php
interface MarkdownRendererInterface
{
    public function render(string $markdown): string;
}
```

### 実装

League CommonMarkをwrapする薄いadapter:

```php
final class CommonMarkRenderer implements MarkdownRendererInterface
{
    public function __construct(
        private readonly CommonMarkConverter $converter,
    ) {
    }

    public function render(string $markdown): string
    {
        return $this->converter->convert($markdown)->getContent();
    }
}
```

### Provider（無害化構成）

Markdown内の生HTMLはエスケープ、`javascript:` linkは拒否 — templateのraw出力（`{{= $bodyHtml }}`）はこの構成が前提:

```php
final class CommonMarkConverterProvider implements ProviderInterface
{
    public function get(): CommonMarkConverter
    {
        return new CommonMarkConverter([
            'html_input' => HtmlFilter::ESCAPE,
            'allow_unsafe_links' => false,
        ]);
    }
}
```

### Module binding

`CommonMarkConverter` は `toInstance()` でなく `toProvider()` — converterは内部にClosureを抱え、prod contextのDI script compile時にserializeできない:

```php
$this->bind(CommonMarkConverter::class)->toProvider(CommonMarkConverterProvider::class)->in(Scope::SINGLETON);
$this->bind(MarkdownRendererInterface::class)->to(CommonMarkRenderer::class)->in(Scope::SINGLETON);
```

### Page Resource

`bodyHtml` はPage Resourceで作る（純粋なpresentation derivative）— template内でparserを生成しない:

```php
public function __construct(
    private readonly MarkdownRendererInterface $markdown,
) {
}

#[Embed(rel: '_self', src: 'app://self/article{?id}')]
public function onGet(int $id): static
{
    // ...
    $body = (string) $this->body['body'];
    $this->body['bodyHtml'] = $this->markdown->render($body);

    return $this;
}
```

## Naming

| 対象 | 規則 | 例 |
|---|---|---|
| 変換interface | `src/Service/` に役割名で置く（library名を出さない） | `MarkdownRendererInterface` |
| 実装 | library名を冠したadapter | `CommonMarkRenderer` |
| Provider | `src/Provider/<生成class>Provider` | `CommonMarkConverterProvider` |
| body key | 変換結果はpresentation derivativeとして `bodyHtml` | `$this->body['bodyHtml']` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 変換を interface（`MarkdownRendererInterface`）越しにDI注入すると決めたか。
- [ ] 変換結果（`bodyHtml`）は Page Resource で作り、template内でparserを生成しないと理解したか。
- [ ] raw出力（`{{= $bodyHtml }}`）の前提となる変換時の無害化（生HTMLエスケープ・unsafe link拒否）を構成すると決めたか。

## Source

- [`src/Resource/Page/Article.php::onGet()`](../src/Resource/Page/Article.php)
- [`src/Service/MarkdownRendererInterface.php`](../src/Service/MarkdownRendererInterface.php)
- [`src/Service/CommonMarkRenderer.php`](../src/Service/CommonMarkRenderer.php)
- [`src/Provider/CommonMarkConverterProvider.php`](../src/Provider/CommonMarkConverterProvider.php)
- [`src/Module/AppModule.php`](../src/Module/AppModule.php)

## Tests

- [`tests/Resource/Page/ArticleTest.php`](../tests/Resource/Page/ArticleTest.php)

## Key points

変換サービスはDIで注入し、Page Resourceで `bodyHtml` を作る。Converterは `html_input: HtmlFilter::ESCAPE` + `allow_unsafe_links: false` で構成し、Markdown内の生HTMLと `javascript:` linkを無害化する — templateのraw出力はこの安全化が前提。`CommonMarkConverter` は `toInstance()` でなく `toProvider()` でbindする（prod contextのDI script compile時にClosure内包instanceはserializeできない）。

## Do not

- 無害化構成なしのConverter出力を `{{= }}` でrawに出さない — League CommonMarkのdefaultは生HTMLと `javascript:` linkをそのまま通すため、`html_input: ESCAPE` + `allow_unsafe_links: false` を欠いたraw出力はXSSになる。

## マスター確認（After）

- [ ] 変換が interface 経由でDI注入され、template に `new` parser が無い。
- [ ] Markdown内の生HTML・unsafe linkが無害化されることを pin。
- [ ] `bodyHtml` がResource側で生成され、`ArticleTest.php` 相当で変換結果を green。

## See also

- [`page-resource-qiq-detail`](./page-resource-qiq-detail.md) — `bodyHtml` を `{{= }}` でraw出力する消費側の詳細ページ
- [`page-resource-test`](./page-resource-test.md) — 無害化（生HTML・unsafe link）をpinするPage HTMLテストの型
- [`api-get-hal-resource`](./api-get-hal-resource.md) — Markdown本文（`body`）を運ぶ参照先App GETの型
- [`admin-prg-form`](./admin-prg-form.md) — Markdown本文を入力するAdmin form側
