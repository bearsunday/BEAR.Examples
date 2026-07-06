# `api-get-hal-resource`

**GET ResourceをHAL+JSONで返す** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** GET resource, HAL JSON, ResourceObject body, API item resource, `onGet`, HAL+JSON応答, リソース取得, HALレンダリング
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** App Resourceで1件の状態をAPI表現として返したい。

## 例

### Resource

状態を連想配列で `$this->body` に置くだけ — JSON化は renderer の仕事（`Author::onGet`）:

```php
public function onGet(int $id): static
{
    $author = $this->author->item($id);
    if ($author === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Author not found', 'id' => $id];

        return $this;
    }

    $this->body = [
        'id' => $author->id,
        'name' => $author->name,
        'email' => $author->email,
        'bio' => $author->bio,
    ];

    return $this;
}
```

`#[Embed]` を併用する場合は scalar を `$this->body += [...]` で足す — `+=` vs `=` の判断は [`hal-embed`](./hal-embed.md) の型を参照（`Article::onGet` が実例）。

### 表現の検証（テスト）

`_links` / `_embedded` は `$ro->body` には現れない。rendered表現は `json_decode((string) $ro, true)` で取り出して検証する（`ArticleTest`）:

```php
$ro = $this->resource->get('app://self/article', ['id' => 1]);

$this->assertSame(200, $ro->code);
$this->assertSame(1, $ro->body['id']);

// The HAL renderer materialises #[Embed] requests under _embedded.
$rendered = json_decode((string) $ro, true);
$this->assertSame($ro->body['authorId'], $rendered['_embedded']['author']['id']);
$this->assertArrayHasKey('goArticleList', $rendered['_links']);
```

## Naming

URIとResource classは1対1 — item と collection は語彙の対で分ける:

| 形 | URI | Class |
|---|---|---|
| item resource | `app://self/article` | `src/Resource/App/Article.php` |
| collection resource | `app://self/articles` | `src/Resource/App/Articles.php` |

Read依存のpropertyは queryable noun — `$this->article->item($id)` と読める `$<entity>` 形（Write側は `$<entity>Cmd`）。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] ResourceObject は状態を `$this->body` に置き、表現（JSON化）は renderer に任せると理解したか。
- [ ] `#[Embed]` を使う場合は scalar を `$this->body += [...]`、使わない場合は `$this->body = [...]` と決めたか。
- [ ] not-found 分岐を先に決めたか（`Code::NOT_FOUND` + message body、[`not-found-response`](./not-found-response.md) 参照）。

## Source

- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Author.php::onGet()`](../src/Resource/App/Author.php)
- [`docs/resources.md`](../docs/resources.md)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Hypermedia/HalEnvelopeContractTest.php`](../tests/Hypermedia/HalEnvelopeContractTest.php)

## Key points

ResourceObjectは状態を `$this->body` に置く。表現はrendererが作る。`_links` / `_embedded` は `$ro->body` には現れず、HAL rendererが表現生成時に付与する — テストでは `json_decode((string) $ro, true)` でrendered表現を検証する。

## Do not

- ResourceでJSON文字列を手作りしない — `json_encode` した文字列を body に置くと renderer が働かず、`_links` / `_embedded` の付与も失われる。他フレームワークのJSON応答（`JsonResponse` 等）に慣れていると陥りやすいが、bodyは連想配列のまま置くのがidiom。

## マスター確認（After）

- [ ] Resource に `json_encode` や手書きJSON文字列が無い。
- [ ] body が連想配列（または `#[Embed]` slot 付き）で構成されている。
- [ ] `ArticleTest.php` 相当で200 + body shape を pin して green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — bodyに詰めるEntityをDBから読む側
- [`not-found-response`](./not-found-response.md) — 404のidiom
- [`hal-link`](./hal-link.md) — `#[Link]` で `_links` に遷移を載せる
- [`hal-embed`](./hal-embed.md) — `#[Embed]` で `_embedded` を埋め込む（`+=` vs `=` の判断もこちら）
- [`json-schema-validation`](./json-schema-validation.md) — 応答shapeを `#[JsonSchema]` で固定する
- [`api-post-input-dto`](./api-post-input-dto.md) — 書き込み側（POST）の相手方
