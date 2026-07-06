# `hal-link`

**HAL `_links` を `#[Link]` で宣言する** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** HAL link, `_links`, `#[Link]`, affordance, Choreography rel, URI template, linkSelf, linkNew, リンク, 遷移, ハイパーメディア, URIテンプレート
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_link.html
- **Use when:** clientが次に遷移できるResourceをHAL linkとして表したい。

## 例

### Resource（item側）

`Article::onGet()` — 一覧・関連Resourceへの遷移を `#[Link]` で宣言する。`{?id}` のURI templateは `$this->body` の値で展開される:

```php
#[Link(rel: 'goArticleList', href: 'app://self/articles')]
#[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
#[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
public function onGet(int $id): static
{
    $article = $this->article->item($id);

    $this->body = [
        'id' => $article->id,
        // ...
        'authorId' => $article->authorId,
        'categoryId' => $article->categoryId,
    ];

    return $this;
}
```

### Resource（collection側）

`Articles::onGet()` — 一覧から個別記事への遷移:

```php
#[Link(rel: 'goArticle', href: 'app://self/article{?id}')]
public function onGet(int $page = 1, int $perPage = 20): static
```

### ALPS Choreography

rel名は `var/alps/profile.json` のChoreography descriptor（`type: safe` の遷移）と一致する:

```json
{"id": "goArticleList", "type": "safe", "rt": "#ArticleList", "title": "View Article List"},
{"id": "goArticle", "type": "safe", "rt": "#Article", "title": "View Article"},
{"id": "goAuthor", "type": "safe", "rt": "#Author", "title": "View Author"}
```

### Contract test

`self` はHAL rendererが自動付与するので、それを除いた資産のrelをpinする:

```php
$rels = array_values(array_diff(array_keys($rendered['_links']), ['self']));
$this->assertSame(['goArticleList', 'goAuthor', 'goCategory'], $rels);
```

## Naming

HAL relはALPSの層で分ける — `_links` と `_embedded` は別の命名空間:

| Where | Source layer | 例 |
|---|---|---|
| `#[Link]` rel | ALPS **Choreography**（遷移動詞） | `goArticleList`, `goAuthor`, `doCreateArticle` |
| `#[Embed]` rel | ALPS **Taxonomy**（entity名詞） | `author`, `category`, `tagList` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] link rel に ALPS Choreography 名（`goArticleList` 等の遷移名）を使うと決めたか。
- [ ] link（遷移名）と embed（Taxonomy名詞）の命名層を混ぜないと理解したか。
- [ ] `#[Link]` の `href` URI templateは **`$this->body` の値**で展開されると理解したか（`#[Embed]` の `src` がrequest引数で束縛されるのと対照的）。

## Source

- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Articles.php::onGet()`](../src/Resource/App/Articles.php)
- [`var/alps/profile.json`](../var/alps/profile.json)

## Tests

- [`tests/Hypermedia/ReaderBrowsesByCategoryTest.php`](../tests/Hypermedia/ReaderBrowsesByCategoryTest.php)
- [`tests/Hypermedia/ReaderBrowsesByTagTest.php`](../tests/Hypermedia/ReaderBrowsesByTagTest.php)
- [`tests/Hypermedia/HalEnvelopeContractTest.php`](../tests/Hypermedia/HalEnvelopeContractTest.php)

## Key points

link relはALPS Choreography名。例: `goArticleList`, `goAuthor`, `goCategory`。`self` linkはHAL rendererが自動付与する。条件付き・動的linkは `$this->body['_links'][$rel] = ['href' => ..., 'templated' => true]` で足せる。

## Do not

- embed用のTaxonomy名（`author` 等の名詞）とlink用のChoreography名（`goAuthor` 等の遷移名）を混ぜない — `#[Embed(rel: 'goAuthor', ...)]` は誤り。`go*` はclientが辿る遷移であり、embedはserverが埋め込むTaxonomy実体。

## マスター確認（After）

- [ ] `#[Link(rel: ...)]` の rel が ALPS profile の Choreography 名と一致。
- [ ] `_links` の href **展開値**（bodyのどのkeyで展開されたか）まで pin している。
- [ ] response の `_links` を辿る workflow test（`ReaderBrowsesBy*Test.php` 相当）が green。

## See also

- [`hal-embed`](./hal-embed.md) — `_embedded` の相手方（Taxonomy名詞でresource requestを埋め込む）
- [`api-get-hal-resource`](./api-get-hal-resource.md) — linkを付けるGET HAL resourceの基本形
- [`alps-profile-ssot`](./alps-profile-ssot.md) — Choreography名のSSOTであるALPS profile
- [`hypermedia-workflow-test`](./hypermedia-workflow-test.md) — `_links` を辿ってrel連鎖を検証するtest
