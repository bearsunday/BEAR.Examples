# `cacheable-response`

**`#[CacheableResponse]`でレスポンス全体をキャッシュする** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** CacheableResponse, response cache, whole content cache, Articles, Categories, レスポンスキャッシュ, 全体キャッシュ, ETag
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** Collection resourceのレスポンス全体をキャッシュし、ETag付きで配信したい。

## 例

### Resource — class-level `#[CacheableResponse]`

cache surfaceはclass-levelの属性1つ。method本体は素のcollection readで、cache primitiveは現れない:

```php
use BEAR\RepositoryModule\Annotation\CacheableResponse;

#[CacheableResponse]
class Categories extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $category,
    ) {
    }

    public function onGet(): static
    {
        $items = $this->category->list();
        $this->body = [
            'items' => array_map(static fn ($c) => [
                'id' => $c->id,
                'slug' => $c->slug,
                'name' => $c->name,
                'description' => $c->description,
                'parentId' => $c->parentId,
            ], $items),
            'count' => count($items),
        ];

        return $this;
    }
}
```

### Pager付きcollectionも同じ形

pager付き一覧（→ [`db-read-list-pager`](./db-read-list-pager.md)）でもclass-levelに付けるだけ。query-string variant（`?categoryId=3` 等）はそれぞれ別のcache entryを持つ:

```php
#[CacheableResponse]
class Articles extends ResourceObject
```

### Test — donut pipelineのログを検証

`RepositoryLoggerInterface` のログでキャッシュ配線を確認する。write後のlist無効化は [`cache-purge`](./cache-purge.md) の型:

```php
$injector = Injector::getInstance('test-hal-api-app');
$this->resource = $injector->getInstance(ResourceInterface::class);
$this->logger = $injector->getInstance(RepositoryLoggerInterface::class);

$ro = $this->resource->get('app://self/articles');
$this->assertSame(200, $ro->code);

$log = (string) $this->logger;
$this->assertStringContainsString('"op":"try-donut-view"', $log);
$this->assertStringContainsString('"op":"put-donut"', $log);
$this->assertStringContainsString('"op":"save-etag"', $log);
```

## Naming

item ↔ collection はclass名の単複で対にする — Query methodの `item` ↔ `list` と語彙を揃える:

| 種別 | 形 | 例 |
|---|---|---|
| item resource | `<Entity>` | `Article`, `Category` |
| collection resource | `<Entity>s`（複数形） | `Articles`, `Categories` |
| collectionのread method | `list()` / `list<Variant>()` | `$this->category->list()` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[CacheableResponse]` は全体コンテンツキャッシュ（embed子も含む）で、`#[Cacheable]` は個別Resourceのキャッシュだと理解したか。
- [ ] Donut cacheと違い、embed子もキャッシュ対象になることを理解したか。

## Source

- [`src/Resource/App/Articles.php`](../src/Resource/App/Articles.php)
- [`src/Resource/App/Categories.php`](../src/Resource/App/Categories.php)

## Tests

- [`tests/Resource/App/CacheTest.php`](../tests/Resource/App/CacheTest.php)
- [`tests/Resource/App/ArticlesTest.php`](../tests/Resource/App/ArticlesTest.php)

## Key points

`#[CacheableResponse]` は全体キャッシュ。embed子も含めてキャッシュされる（本showcaseのArticles/Categories自体にはembed子が無い — embed込みの実例はmanualのBlogPosting例）。TTLは `DonutRepositoryInterface::put($this, ttl:, sMaxAge:)` で指定でき、default TTLはCDN module依存（tag無効化対応CDNなら実質無期限、それ以外は10秒）。ETagによる304応答は [`conditional-request-304`](./conditional-request-304.md) が担う。

## Do not

- 3つのcache属性（`#[Cacheable]` / `#[DonutCache]` / `#[CacheableResponse]`）を混同しない — `#[Cacheable]` は個別Resourceのキャッシュ（→ [`cacheable-leaf`](./cacheable-leaf.md)）、`#[DonutCache]` はembed子（穴）を毎回実行する部分キャッシュ（→ [`donut-cache`](./donut-cache.md)）、`#[CacheableResponse]` はembed子も含む全体キャッシュ。

## マスター確認（After）

- [ ] GETでRepositoryLoggerのログに `try-donut-view` / `put-donut` / `save-etag` が出ることを `CacheTest.php` 相当で green。

## See also

- [`cacheable-leaf`](./cacheable-leaf.md) — `#[Cacheable]` 単体の個別Resourceキャッシュ
- [`donut-cache`](./donut-cache.md) — `#[DonutCache]` の部分キャッシュ（穴は毎回実行）
- [`cache-purge`](./cache-purge.md) — write時に `#[Purge]` でcollection cacheを無効化する
- [`cache-embed-dependency`](./cache-embed-dependency.md) — `#[Embed]` 子依存のtag自動合成
- [`conditional-request-304`](./conditional-request-304.md) — ETagを使った条件付きリクエスト（304）
- [`db-read-list-pager`](./db-read-list-pager.md) — Articles一覧の読み取り自体の型
