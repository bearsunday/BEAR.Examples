# `hal-embed`

**HAL `_embedded` を `#[Embed]` と `addQuery()` で作る** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** HAL embed, `_embedded`, `#[Embed]`, `addQuery`, embedded resource, Taxonomy rel, 埋め込み, リソース埋め込み, リソースグラフ, 関連リソース
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_link.html
- **Use when:** primary resourceの応答に関連Resourceを埋め込みたい。

## 例

### Resource（親）

`#[Embed]` が関連Resourceへの **request** を `$this->body[$rel]` に注入する。`onGet` では `addQuery()` で引数を渡し、scalar は `+=` で足す:

```php
#[Embed(rel: 'author', src: 'app://self/author')]
#[Embed(rel: 'category', src: 'app://self/category')]
#[Embed(rel: 'tagList', src: 'app://self/tags')]
public function onGet(int $id): static
{
    $article = $this->article->item($id);
    if ($article === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $id];

        return $this;
    }

    $this->body['author']->addQuery(['id' => $article->authorId]);
    $this->body['category']->addQuery(['id' => $article->categoryId]);
    $this->body['tagList']->addQuery(['articleId' => $article->id]);

    $this->body += [
        'id' => $article->id,
        'slug' => $article->slug,
        'title' => $article->title,
        // ...
    ];

    return $this;
}
```

### Resource（embedされる子）

embedされる側は普通のResource。親の `addQuery(['articleId' => ...])` が **request method引数**に束縛される:

```php
class Tags extends ResourceObject
{
    public function onGet(int|null $articleId = null): static
    {
        $items = $articleId === null
            ? $this->tag->list()
            : $this->tag->listByArticle($articleId);

        $this->body = [
            'items' => array_map(static fn ($t) => [
                'id' => $t->id,
                'slug' => $t->slug,
                'name' => $t->name,
            ], $items),
            'count' => count($items),
        ];

        return $this;
    }
}
```

### Contract test

評価はrendering時。`(string)` castでHALがmaterialiseされ、Taxonomy名詞が `_embedded` に現れる:

```php
$rendered = json_decode((string) $article, true);

$this->assertSame(['author', 'category', 'tagList'], array_keys($rendered['_embedded']));
```

## Naming

HAL relはALPSのlayerで分ける — `_embedded` はTaxonomy、`_links` はChoreography:

| Where | ALPS layer | 例 |
|---|---|---|
| `#[Embed]` rel | **Taxonomy**（entity名詞） | `author`, `category`, `tagList` |
| `#[Link]` rel | **Choreography**（遷移動詞） | `goArticleList`, `goAuthor` |

`#[Embed(rel: 'goAuthor', ...)]` は誤り — `go*` はクライアントが辿る遷移であり、embedはサーバーが含めるtaxonomyインスタンス。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] embed rel に Taxonomy 名詞（`author`/`category`/`tagList` 等）を使うと決めたか。
- [ ] `#[Embed]` が先に Request slot を作るため、scalar は `$this->body += [...]` で足すと理解したか。

## Source

- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Author.php::onGet()`](../src/Resource/App/Author.php)
- [`src/Resource/App/Category.php::onGet()`](../src/Resource/App/Category.php)
- [`src/Resource/App/Tags.php::onGet()`](../src/Resource/App/Tags.php)

## Tests

- [`tests/Hypermedia/HalEnvelopeContractTest.php`](../tests/Hypermedia/HalEnvelopeContractTest.php)
- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)

## Key points

`#[Embed]` が先にRequest slotを作るため、scalar fieldsは `$this->body += [...]` で足す。embedされるのはresource **request**（lazy）で評価はrendering時 — `addQuery()` は引数の追加、`withQuery()` は置換で、いずれもrender前に呼ぶ。`src` にURI template（`/author{?id}` 等）を使うと **request method引数**が束縛される（`#[Link]` の `$body` 束縛と異なる）。`rel: '_self'` は例外的に**eager**で、子のbodyを自身へflattenし子のstatus codeを伝播する（PageがAppを包む実例: `src/Resource/Page/Article.php` — [`page-resource-qiq-detail`](./page-resource-qiq-detail.md)）。

## Do not

- embed slotを `$this->body = [...]` で上書きしない — `#[Embed]` がonGet実行前に注入したRequest slotが消え、`_embedded` から子が黙って落ちる。他のKataでは `=` が基本形だが、`#[Embed]` を持つ `onGet` だけは `+=` で足す。

## マスター確認（After）

- [ ] `#[Embed]` を持つResourceが scalar を `+=` で足し、embed slot を `=` で潰していない。
- [ ] embed rel が `go*` でない（Taxonomy名詞）。
- [ ] `_embedded` に子Resourceが現れることを `HalEnvelopeContractTest.php` 相当で green。

## See also

- [`hal-link`](./hal-link.md) — `_links` 側（Choreography動詞）の相方
- [`api-get-hal-resource`](./api-get-hal-resource.md) — embedを付ける前の基本GET Resource
- [`page-resource-qiq-detail`](./page-resource-qiq-detail.md) — `rel: '_self'` のeager embedでPageがAppを包む実例
- [`async-embed-parallel`](./async-embed-parallel.md) — embedがrequestであることを利用した並列実行
- [`crawl-data-loader`](./crawl-data-loader.md) — embed requestのDataLoaderバッチ
- [`cache-embed-dependency`](./cache-embed-dependency.md) — `#[Embed]` によるキャッシュ依存の自動追跡
- [`donut-cache`](./donut-cache.md) — embed境界を使った部分キャッシュ
