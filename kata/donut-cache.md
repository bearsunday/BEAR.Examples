# `donut-cache`

**`#[DonutCache]`で部分キャッシュを示す** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** DonutCache, donut caching, partial cache, donut hole, ArticlePreview, ドーナツキャッシュ, 部分キャッシュ, ドーナツホール
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** Resource全体のうち、embedされた非キャッシュ可能部分を除いたキャッシュ可能部分を分離してキャッシュしたい。

## 例

### Resource

cache surfaceはclass-levelの `#[DonutCache]` のみ。本リポジトリの `ArticlePreview` はscalar-onlyの最小実例で、method本体は素のread（[`db-read-one-entity`](./db-read-one-entity.md) と同じ形）。donut-holeのplaceholderはstring-renderer向けのためHALでは示していない:

```php
use BEAR\RepositoryModule\Annotation\DonutCache;

#[DonutCache]
class ArticlePreview extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
    ) {
    }

    public function onGet(int $id): static
    {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $article->id,
            'title' => $article->title,
            'status' => $article->status->value,
            'authorId' => $article->authorId,
            'cachePattern' => 'DonutCache explicit HAL preview',
        ];

        return $this;
    }
}
```

### Test

donut pipelineの動作は `RepositoryLoggerInterface` のログで観測する。GETで `try-donut-view` / `put-donut` が記録される:

```php
$injector = Injector::getInstance('test-hal-api-app');
$this->resource = $injector->getInstance(ResourceInterface::class);
$this->logger = $injector->getInstance(RepositoryLoggerInterface::class);
$this->logger->reset();

$ro = $this->resource->get('app://self/cache/articlepreview', ['id' => 1]);
$this->assertSame(200, $ro->code);

$log = (string) $this->logger;
$this->assertStringContainsString('"op":"try-donut-view"', $log);
$this->assertStringContainsString('"op":"put-donut"', $log);
$this->assertStringContainsString('"uri":"app://self/cache/articlepreview?id=1"', $log);
```

## Naming

cache showcaseのResourceは正規CMS Resourceとは別に `Cache/` 配下に置く。依存プロパティの命名は通常のResourceと同じ:

| 種別 | 形 | 例 |
|---|---|---|
| showcase Resource | `src/Resource/App/Cache/<Name>.php`（URI `app://self/cache/<name>`） | `Cache\ArticlePreview` |
| read依存 | `$<entity>`（`<Entity>QueryInterface`） | `private ArticleQueryInterface $article` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[DonutCache]` はembed子（hole）が非キャッシュ可能な時に使い、全体コンテンツキャッシュの対概念は `#[CacheableResponse]` と理解したか（manual: DonutCacheでは全体はキャッシュされずETagも出ない）。
- [ ] `#[Cacheable]`（QueryRepositoryの個別Resourceキャッシュ・TTL型）とは別枠の使い分けと理解したか。

## Source

- [`src/Resource/App/Cache/ArticlePreview.php`](../src/Resource/App/Cache/ArticlePreview.php)

## Tests

- [`tests/Resource/App/CacheTest.php`](../tests/Resource/App/CacheTest.php)

## Key points

`#[DonutCache]` では全体が動的扱いになるため全体キャッシュは作られずETagも出力されない。donut部分の計算は再利用され、holeがcacheableな場合（donut hole cache）は依存解決が自動で行われる。本リポジトリの `ArticlePreview` はscalar-onlyの最小実例（donut-holeのplaceholderはstring-renderer向けのためHALでは示していない）— hole再描画の実挙動はmanualを一次資料とする。

## Do not

- 本プロジェクトでは explicit `#[DonutCache]` にHALの `#[Embed]` を組み合わせない — donut-holeのplaceholderはstring rendering（`DonutRequest::__toString()`）前提で、HALのobject rendererでは未描画のrequest placeholderがobjectとしてserializeされてしまう。HALでのembed依存キャッシュは [`cache-embed-dependency`](./cache-embed-dependency.md) で示す。

## マスター確認（After）

- [ ] class に `#[DonutCache]` があり、GETでRepositoryLoggerのログに `try-donut-view` / `put-donut` が出ることを `CacheTest.php` 相当で green。

## See also

- [`cacheable-response`](./cacheable-response.md) — 全体コンテンツキャッシュ（ETagあり）。`#[DonutCache]` の対概念
- [`cacheable-leaf`](./cacheable-leaf.md) — `#[Cacheable]`（QueryRepositoryの個別Resourceキャッシュ）の基本形
- [`cache-embed-dependency`](./cache-embed-dependency.md) — `#[Embed]` 親が子leafのtagを自動合成する（Shape A）
- [`cache-body-derived-dependency`](./cache-body-derived-dependency.md) — body由来の可変長依存を `fromAssoc()` で宣言する（Shape B）
- [`cache-purge`](./cache-purge.md) — `#[Purge]` でwrite時に別URI（collection）を無効化する
- [`conditional-request-304`](./conditional-request-304.md) — `ETag` を使った条件付きリクエスト（304）
