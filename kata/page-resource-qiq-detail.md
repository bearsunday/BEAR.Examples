# `page-resource-qiq-detail`

**Page Resourceで1件詳細HTMLを描画する** · [← 索引に戻る](../index.md)

- **Category:** HTML / Page
- **Status:** `canonical`
- **Aliases:** Page Resource, Qiq, HTML detail, template variables, article page, setLayout, setBlock, html context, self embed, _self, Reachability, HTML描画, 詳細ページ, Qiqテンプレート
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/html-qiq.html
- **Use when:** App Resourceの状態をHTML詳細ページとして表示するPage Resourceを作りたい。

## 例

### App Resource（参照先）

Articleのグラフ（author / category / tagList）はApp側で `#[Embed]` により合成済み — Pageはこれを参照する（Reachability、`docs/conventions.md` §4）:

```php
#[Embed(rel: 'author', src: 'app://self/author')]
#[Embed(rel: 'category', src: 'app://self/category')]
#[Embed(rel: 'tagList', src: 'app://self/tags')]
public function onGet(int $id): static
```

### Page Resource（self embed）

`_self` embedはeager実行 — App bodyがPage bodyへflattenされ、Appのstatus codeが伝播する。`onGet` は404を先にguardし、子Requestを配列化して、純粋なpresentation derivative（`bodyHtml` 等）だけを足す:

```php
#[Embed(rel: '_self', src: 'app://self/article{?id}')]
public function onGet(int $id): static
{
    if ($this->code === Code::NOT_FOUND) {
        $this->body = ['message' => 'Article not found'];

        return $this;
    }

    $this->body['author'] = $this->materialise($this->body['author']);
    $this->body['category'] = $this->materialise($this->body['category']);
    $tagListBody = $this->materialise($this->body['tagList']);
    $this->body['tags'] = $tagListBody['items'] ?? [];
    unset($this->body['tagList']);

    // Pure presentation derivatives (conventions §4 exception list).
    $body = (string) $this->body['body'];
    $this->body['bodyHtml'] = $this->markdown->render($body);

    return $this;
}
```

App bodyに残る子Requestは、Page側で評価して配列にする（App Resourceは自前のHTML templateを持たないため、Requestのままrendererへ渡さない）:

```php
private function materialise(mixed $request): array|null
{
    assert($request instanceof Request);
    $ro = $request->__invoke();

    return $ro->code === Code::OK && is_array($ro->body) ? $ro->body : null;
}
```

### Qiqテンプレート

Qiqは暗黙エスケープしないため出力は `{{h }}` で明示、layoutは `setLayout()` + `setBlock()` で組む。冒頭のguardは4xx時にもtemplateが実行されることへの対策（`{{= $bodyHtml }}` はMarkdown変換済みHTMLのraw出力）:

```php
<?php
if (! isset($id, $slug, $title, $status)) {
    throw new \BEAR\Kata\Exception\ArticleNotFoundException();
}
?>
{{ setLayout('layout/Default') }}
{{ setBlock('title') ~}}{{h $title }} - BEAR.Kata{{ endBlock() }}
{{ setBlock('header') ~}}<h1 class="Article">Article Detail</h1>{{ endBlock() }}
<main>
  <article class="Article">
    <h2 class="title">{{h $title }}</h2>
    <span class="status" data-status="{{h $status }}">{{h $status }}</span>
    <div class="body">{{= $bodyHtml }}</div>
  </article>
</main>
```

### HtmlModule

QiqModuleにtemplateディレクトリを渡し、rendererを束ねる。template名はResourceクラスのファイルパスから導出される（`src/Resource/Page/Article.php` → `templates/Page/Article.php`）:

```php
protected function configure(): void
{
    $this->install(new QiqModule(dirname(__DIR__, 2) . '/templates'));
    $this->bind(RenderInterface::class)->to(CmsQiqRenderer::class)->in(Scope::SINGLETON);
    $this->bind(ThrowableHandlerInterface::class)->to(HtmlThrowableHandler::class);
}
```

## Naming

| 対象 | 規則 | 例 |
|---|---|---|
| Page Resource | `src/Resource/Page/<Entity>.php` — Appと同名クラスでHTML面を持つ | `Page/Article.php` |
| Qiqテンプレート | Resourceの `src/Resource/` 以下のパスを `templates/` に写す | `templates/Page/Article.php` |
| `#[Embed]` rel | taxonomy名詞（`author`, `tagList`）。統合viewへのflattenのみ `_self` | `rel: '_self'` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] Reachability原則を理解したか — 情報はApp Resource（`app://`）に住み、PageはQuery Interfaceを再注入せず**Appを参照**する（`docs/conventions.md` §4）。
- [ ] 複数の子を統合viewに融合する詳細ページは **self embed**（`#[Embed(rel: '_self', src: 'app://self/article{?id}')]`）、子の表現をそのまま置く一覧は **normal embed**（`AuthorList` の型）と使い分けを決めたか。
- [ ] Pageが持ってよいのは純粋なpresentation derivative（`bodyHtml` / 表示用ラベル / guard）のみと理解したか。

## Source

- [`src/Resource/Page/Article.php::onGet()`](../src/Resource/Page/Article.php)
- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`templates/Page/Article.php`](../templates/Page/Article.php)
- [`src/Renderer/CmsQiqRenderer.php`](../src/Renderer/CmsQiqRenderer.php)
- [`src/Module/HtmlModule.php`](../src/Module/HtmlModule.php)

## Tests

- [`tests/Resource/Page/ArticleTest.php`](../tests/Resource/Page/ArticleTest.php)

## Key points

`_self` embedはeager実行 — App bodyがPage bodyへflattenされ、**Appのstatus codeが伝播する**（Appの404がPageの404になる）。App bodyに残る子Request（author/category/tagList）はPageの `onGet` でmaterialiseして配列にする（App Resourceは自前のHTML templateを持たないため、Requestのままrendererへ渡さない）。Qiqは暗黙エスケープしないため出力は `{{h }}` で明示エスケープし、layoutは `setLayout()` + `setBlock()` で組む。template名はResourceクラスのファイルパスから導出。

## Do not

- PageにQuery Interfaceを注入してAppと同じグラフを再組み立てしない。Appに無い情報をPageで組むのはreachability hole — 情報は `app://` に住まわせ、Pageは [`hal-embed`](./hal-embed.md) でAppを参照するだけにする。

## マスター確認（After）

- [ ] Page Resource に Query Interface の注入が無く、`#[Embed]` でAppを参照している。
- [ ] App側の404がPageの404として伝播する（`ArticleTest.php` のnotFoundケース相当で green）。
- [ ] XSSエスケープ（`{{h }}` 漏れ）をfield値のescape検証で pin。
- [ ] `Resource/Page/ArticleTest.php` 相当でHTML描画と200を green。

## See also

- [`page-resource-list`](./page-resource-list.md) — 一覧HTML（normal embedで子の表現をそのまま置く相手方）
- [`hal-embed`](./hal-embed.md) — `#[Embed]` の基本形（`_self` と normal の土台）
- [`markdown-to-html`](./markdown-to-html.md) — `bodyHtml` derivativeを生むMarkdown変換
- [`api-get-hal-resource`](./api-get-hal-resource.md) — 参照先となるApp GETの型
- [`not-found-response`](./not-found-response.md) — App側の404 idiom（Pageへ伝播する元）
- [`page-resource-test`](./page-resource-test.md) — Page HTMLのテスト型
- [`admin-prg-form`](./admin-prg-form.md) — 書き込みを持つPage（フォーム）の型
