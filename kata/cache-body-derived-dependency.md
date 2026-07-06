# `cache-body-derived-dependency`

**body由来の可変長依存を `fromAssoc()` で宣言する** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** variable dependencies, body-derived dependency, `UriTagInterface::fromAssoc`, surrogate key, article tags cache, サロゲートキー, 可変長依存, タグ無効化
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** DB結果から得たN個の子URIに依存するResourceをcacheしたい。

## 例

### 親Resource — `fromAssoc()` でSurrogate-Keyを宣言

依存集合はbody（DB行）から導出されるN個のtag URIで、静的 `#[Embed]` では表せない。`fromAssoc()` の戻り値を `Header::SURROGATE_KEY` に代入する — これがshowcase唯一のmanual cache primitive。空の時はheaderをセットしない:

```php
#[Cacheable]
class ArticleTags extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly ArticleTagCommandInterface $articleTagCmd,
        private readonly UriTagInterface $uriTag,
    ) {
    }

    public function onGet(int $articleId): static
    {
        $items = array_map(
            static fn ($t) => ['id' => $t->id, 'slug' => $t->slug, 'name' => $t->name],
            $this->tag->listByArticle($articleId),
        );

        if ($items !== []) {
            $this->headers[Header::SURROGATE_KEY] = $this->uriTag->fromAssoc('app://self/cache/tag{?id}', $items);
        }

        $this->body = [
            'articleId' => $articleId,
            'cachePattern' => 'Cacheable + UriTagInterface::fromAssoc',
            'items' => $items,
            'count' => count($items),
        ];

        return $this;
    }
}
```

### write側 — self purge と依存集合の再構築

showcase自身のwrite入口。`RefreshSameCommand`（`#[Cacheable]` のdefault Commands）がself URI tagをpurgeし、次のGETが再クエリして依存集合を作り直す:

```php
public function onPut(int $articleId, array $tagIds): static
{
    $this->articleTagCmd->clear($articleId);
    foreach ($tagIds as $tagId) {
        $this->articleTagCmd->link($articleId, $tagId);
    }

    $this->code = Code::OK;
    $this->body = ['id' => $articleId];

    return $this;
}
```

### 依存先leaf — Tag

依存先の子は素の `#[Cacheable]` leaf（→ [`cacheable-leaf`](./cacheable-leaf.md)）。cache primitiveはclassに一切現れない。子tagへのPUTがSurrogate-Key経由で、その子に依存する親cacheだけを連鎖無効化する:

```php
#[Cacheable]
class Tag extends ResourceObject
{
    public function onPut(int $id, string $slug, string $name): static
    {
        if ($this->tag->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Tag not found', 'id' => $id];

            return $this;
        }

        $this->tagCmd->update($id, $slug, $name);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }
}
```

### CacheShowcaseModule — demo/test用ArrayAdapter override

非prodコンテキストのQueryRepository cacheはNullAdapterのため、demo/testではArrayAdapterを重ねてETag・tag無効化を実際に動かす:

```php
final class CacheShowcaseModule extends AbstractModule
{
    #[Override]
    protected function configure(): void
    {
        $this->bind(AdapterInterface::class)->annotatedWith(ResourceObjectPool::class)->toInstance(new ArrayAdapter());
    }
}
```

テストでの重ね方:

```php
$injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
```

## Naming

このKataで触る名前 — 関連での一覧read、link-table write、補助Commandプロパティ:

| 種別 | 形 | 例 | SQL ファイル |
|---|---|---|---|
| 関連での一覧read | `list<Variant>` | `listByArticle(int $articleId)` | `tag_list_by_article.sql` |
| link-table write | 命令形動詞 | `clear` / `link` | `article_tag_clear.sql` / `article_tag_link.sql` |
| 補助entityのCommandプロパティ | `$<entity><Role>` | `private ArticleTagCommandInterface $articleTagCmd` | — |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 依存先が**可変長**（N個）で静的 `#[Embed]` では表せないことを確認したか。
- [ ] 唯一のmanual cache primitiveを `UriTagInterface::fromAssoc(...)` に限定すると決めたか。
- [ ] demo/testでは `CacheShowcaseModule`（ArrayAdapter override）を重ねると理解したか。

## Source

- [`src/Resource/App/Cache/ArticleTags.php`](../src/Resource/App/Cache/ArticleTags.php)
- [`src/Resource/App/Cache/Tag.php`](../src/Resource/App/Cache/Tag.php)
- [`src/Module/CacheShowcaseModule.php`](../src/Module/CacheShowcaseModule.php)

## Tests

- [`tests/Resource/App/Cache/ArticleTagsCacheTest.php`](../tests/Resource/App/Cache/ArticleTagsCacheTest.php)

## Key points

`UriTagInterface::fromAssoc('app://self/cache/tag{?id}', $items)` が唯一のmanual cache primitive。戻り値は `$this->headers[Header::SURROGATE_KEY]` に代入する（複数tagはspace区切り。単一URI依存なら `($this->uriTag)(new Uri(...))` でもよい）。「1箇所だけ」の不変条件は `ArticleTagsCacheTest::testSourceHasExactlyOneFromAssocCall()` がreflectionでpinしている。なお main `app://self/article` へのwriteはこのshowcase cacheをpurgeしない（意図的スコープ外）— 本番で同じ依存が要る場合はwrite側に明示的無効化を足す。

## Do not

- 空のtag headerをセットしない — `fromAssoc([])` は `''` を返し、Symfonyのtag-aware cache adapterが空tagを拒否する。`$items === []` の時はheader自体を未セットのままにする（self URI tagによる無効化はframeworkが引き続き行う）。

## マスター確認（After）

- [ ] 依存宣言が `fromAssoc()` 1箇所に集約され、空の時はheaderをセットしない。
- [ ] 子tagの1つを更新すると当該親cacheだけが無効化され、無関係な子tagの更新では無効化されないことを `ArticleTagsCacheTest.php` 相当で green。

## See also

- [`cacheable-leaf`](./cacheable-leaf.md) — 依存先leafの型（`#[Cacheable]` 単体・user-zero-code）
- [`cache-embed-dependency`](./cache-embed-dependency.md) — 静的に表せる単一子依存は `#[Embed]` で自動合成する
- [`donut-cache`](./donut-cache.md) — 明示的 `#[DonutCache]` の型
- [`cache-purge`](./cache-purge.md) — write側からの明示的purge
- [`conditional-request-304`](./conditional-request-304.md) — ETagで304を返す
- [`db-link-table-sync`](./db-link-table-sync.md) — tag関連の `clear` / `link` 書き込みの型
