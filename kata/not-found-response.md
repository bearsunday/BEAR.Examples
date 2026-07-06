# `not-found-response`

**見つからないResourceを404にする** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** 404, not found, missing entity, error body, not found branch, 404応答, 見つからない, エラーページ
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** Queryが `null` を返した時にResourceで404を返したい。

## 例

### App Resource

`null` なら throw せず、`Code::NOT_FOUND` と message body を置く:

```php
$article = $this->article->item($id);
if ($article === null) {
    $this->code = Code::NOT_FOUND;
    $this->body = ['message' => 'Article not found', 'id' => $id];

    return $this;
}
```

### Page Resource

`_self` embed が App の status code を伝播するので、Page は 404 を検知して message body に差し替えるだけ:

```php
#[Embed(rel: '_self', src: 'app://self/article{?id}')]
public function onGet(int $id): static
{
    if ($this->code === Code::NOT_FOUND) {
        $this->body = ['message' => 'Article not found'];

        return $this;
    }

    // ...（通常時の body 構築）
}
```

### Template guard

Page template は 4xx でも呼ばれる。冒頭でドメイン例外を throw して guard する:

```php
if (! isset($id, $slug, $title, $status)) {
    throw new \BEAR\Kata\Exception\ArticleNotFoundException();
}
```

### Renderer

`CmsQiqRenderer::render()` が guard の throw を catch し、`templates/Error.php` を描画する。code>=500 は本文 template を呼ばず即 Error 描画:

```php
if ($ro->code >= 500) {
    return $this->renderError($ro);
}

try {
    return $this->renderTemplate($ro, $vars);
} catch (Throwable) {
    if ($ro->code >= 400) {
        return $this->renderError($ro);
    }

    $ro->code = 500;

    return $this->renderError($ro);
}
```

## Naming

404 body と template guard 例外の形:

| 対象 | 形 | 例 |
|---|---|---|
| 404 body | `['message' => '<Entity> not found', 'id' => $id]` | `'Article not found'` |
| Guard 例外 | `<Entity>NotFoundException` — entityごと、`src/Exception/` に置く | `ArticleNotFoundException` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] not-found を例外ではなくResource側で404 bodyとして表現すると決めたか。
- [ ] Page template は4xxでも呼ばれるため guard を置くと理解したか。

## Source

- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`src/Resource/Page/Article.php::onGet()`](../src/Resource/Page/Article.php)
- [`templates/Page/Article.php`](../templates/Page/Article.php)
- [`src/Renderer/CmsQiqRenderer.php`](../src/Renderer/CmsQiqRenderer.php)
- [`templates/Error.php`](../templates/Error.php)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Resource/Page/ArticleTest.php`](../tests/Resource/Page/ArticleTest.php)

## Key points

not-found readは例外ではなくResourceで404 bodyを置く。Page側は `_self` embed経由でAppの404がそのまま伝播する（[`page-resource-qiq-detail`](./page-resource-qiq-detail.md)）。Page templateは4xxでも呼ばれるためguardを置く — guardは `ArticleNotFoundException` をthrowし、`CmsQiqRenderer::render()` がcatchしてError templateを描画する（code>=500は本文templateを呼ばず即Error描画）。

## Do not

- not-found表現に `src/` から generic な `throw new \RuntimeException` を投げない — template guard で投げてよいのは `src/Exception/` のドメイン例外（`ArticleNotFoundException` 等）だけ。

## マスター確認（After）

- [ ] `src/` に not-found用の generic `throw new \RuntimeException` 等が無い（必要なら `src/Exception/` のドメイン例外）。
- [ ] Page template に entity 不在時の guard がある。
- [ ] 未存在IDで App=404 / Page=Error template描画（本文templateが現れない）を両testで green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — Queryが `Entity|null` を返す読み取りの型（404の入口）
- [`api-get-hal-resource`](./api-get-hal-resource.md) — 404分岐を含むGET Resourceの全体形
- [`page-resource-qiq-detail`](./page-resource-qiq-detail.md) — `_self` embedでAppの404が伝播するPage詳細
- [`error-status-mapping`](./error-status-mapping.md) — 例外→HTTP statusのマッピング
- [`page-resource-test`](./page-resource-test.md) — Error template描画をpinするPage test
