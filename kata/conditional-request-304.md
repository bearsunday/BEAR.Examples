# `conditional-request-304`

**条件付きリクエスト（If-None-Match → 304）で転送を省く** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** conditional request, 304 Not Modified, If-None-Match, ETag revalidation, HttpCacheInterface, isNotModified, 条件付きリクエスト, 再検証
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** クライアントが保持するETagで再検証し、変更が無ければbodyを送らず304で応答したい。

## 例

### Bootstrap（routing前の304判定）

routingの前に `isNotModified()` を検査し、hitならresourceを実行せず304で終える:

```php
$app = Injector::getInstance($context)->getInstance(AppInterface::class);
if ($app->httpCache->isNotModified($server)) {
    $app->httpCache->transfer();

    return 0;
}

$request = $app->router->match($globals, $server);
```

### HttpCache（QueryRepository — external package）

判定は「`If-None-Match` のETagがstorageに在るか」だけ。ETagの生成も失効もQueryRepositoryの管轄で、アプリ側は関与しない:

```php
public function isNotModified(array $server): bool
{
    return isset($server[Header::HTTP_IF_NONE_MATCH]) && $this->storage->hasEtag($server[Header::HTTP_IF_NONE_MATCH]);
}

public function transfer()
{
    http_response_code(304);
}
```

### Resource（アプリ側は `#[Cacheable]` だけ）

leafは [`cacheable-leaf`](./cacheable-leaf.md) の型そのもの。ETag / Last-Modified付与のためのコードは1行も現れない:

```php
#[Cacheable]
class Author extends ResourceObject
```

### 検証（If-None-Match の hit / miss）

同一ETagで再検証するとhit、write後はmissになる:

```php
$first = $this->resource->get('app://self/cache/author', ['id' => 1]);

$this->assertTrue($this->httpCache->isNotModified([
    Header::HTTP_IF_NONE_MATCH => $first->headers[Header::ETAG],
]));
```

## Naming

この型に固有の命名はほぼ無い — ETag / 304の機構はフレームワーク側（`HttpCacheInterface`）が持ち、アプリ側コードには現れない。showcase leafのResourceは通常どおりの依存命名に従う:

| 依存 | プロパティ | 例 |
|---|---|---|
| Read | `$<entity>` | `private AuthorQueryInterface $author` |
| Write | `$<entity>Cmd` | `private AuthorCommandInterface $authorCmd` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] ETag付与は `#[Cacheable]` / `#[CacheableResponse]`（QueryRepository）に任せ、手書きしないと決めたか。
- [ ] 304判定はResourceではなくbootstrap（routing前）で行うと理解したか。
- [ ] write時のETag無効化はAOPがキャッシュ無効化と連動して管理することを理解したか。

## Source

- [`src/Bootstrap.php`](../src/Bootstrap.php)
- [`vendor/bear/query-repository/src/HttpCache.php`](../vendor/bear/query-repository/src/HttpCache.php) *(external package)*
- [`src/Resource/App/Cache/Author.php`](../src/Resource/App/Cache/Author.php)

## Tests

- [`tests/Resource/App/Cache/AuthorCacheTest.php`](../tests/Resource/App/Cache/AuthorCacheTest.php)
- [`tests/Resource/App/Cache/AuthorProfileCacheTest.php`](../tests/Resource/App/Cache/AuthorProfileCacheTest.php)
- [`tests/Resource/App/Cache/ArticleTagsCacheTest.php`](../tests/Resource/App/Cache/ArticleTagsCacheTest.php)

## Key points

`HttpCacheInterface::isNotModified($server)` が `If-None-Match` を検査し、hitなら `transfer()` でrouting前に304を返す（`src/Bootstrap.php`）。ETag / Last-ModifiedはQueryRepositoryが自動付与し、writeで自動無効化される。304はbody転送もresource実行も省くため、計算資源とネットワーク資源の両方を節約する。

## Do not

- ResourceでETag文字列を手計算しない — ETagはQueryRepositoryが付与から失効まで管理する。手書きすると、routing前の304判定・write時の自動無効化との連動が壊れる。

## マスター確認（After）

- [ ] GET応答に `ETag` / `Last-Modified` が自動付与される。
- [ ] 同一ETagの `If-None-Match` で `isNotModified()` が true、write後は false になることを `AuthorCacheTest.php` 相当で green。

## See also

- [`cacheable-leaf`](./cacheable-leaf.md) — `#[Cacheable]` でETagが自動付与される最小のleaf
- [`cacheable-response`](./cacheable-response.md) — `#[CacheableResponse]` 側のETag付与
- [`cache-purge`](./cache-purge.md) — write時の無効化（ETag失効の仕組み）
- [`cache-embed-dependency`](./cache-embed-dependency.md) — embed依存のタグ伝播で親のETagも失効させる
- [`donut-cache`](./donut-cache.md) — 部分キャッシュと組み合わせる発展形
