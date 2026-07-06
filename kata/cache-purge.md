# `cache-purge`

**`#[Purge]`でwrite時にcollection cacheを手動無効化する** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** Purge, cache invalidation, manual purge, collection cache, #[Purge], キャッシュ無効化, パージ, 手動無効化
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** write操作（POST/PUT/DELETE）後に、関連collection resourceのキャッシュを手動で無効化したい。

## 例

### write methodごとに付ける（Article）

POST/PUT/DELETE のそれぞれが同じcollection URIを指定する。無効化されるのは書き込んだitem自身ではなく `app://self/articles`（一覧）のcache:

```php
use BEAR\RepositoryModule\Annotation\Purge;

#[Purge(uri: 'app://self/articles')]
public function onPost(#[Input] ArticleCreateInput $input): static { /* ... */ }

#[Purge(uri: 'app://self/articles')]
public function onPut(#[Input] ArticleUpdateInput $input): static { /* ... */ }

#[Purge(uri: 'app://self/articles')]
public function onDelete(int $id): static { /* ... */ }
```

### 完全な例（Category::onDelete）

purge対象はitem URI（`app://self/category`）ではなくcollection URI（`app://self/categories`）:

```php
#[Purge(uri: 'app://self/categories')]
public function onDelete(int $id): static
{
    if ($this->category->item($id) === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Category not found', 'id' => $id];

        return $this;
    }

    $this->categoryCmd->delete($id);
    $this->code = Code::NO_CONTENT;
    $this->body = [];

    return $this;
}
```

## Naming

Purge対象のURIは複数形のcollection resource — `item` ↔ `list` の語彙対がresource shapeにも及ぶ（`Article`（item）↔ `Articles`（collection））:

| Resource shape | URI | 例 |
|---|---|---|
| item | `app://self/<entity>` | `app://self/article`, `app://self/category` |
| collection | `app://self/<entities>` | `app://self/articles`, `app://self/categories` |

`#[Purge(uri:)]` に渡すのはcollection側。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[Purge(uri: 'app://self/<collection>')]` をwrite methodに付け、該当collection cacheを無効化すると決めたか。
- [ ] Purge対象はcollection URI（`articles`, `categories`）で、item URIでないことを確認したか。
- [ ] purgeされるのは指定したcanonical URIのエントリのみで、query-string variant（`?categoryId=3` 等）は残ると理解したか（`docs/scope.md` D1）。

## Source

- [`src/Resource/App/Article.php::onPost()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Article.php::onPut()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Article.php::onDelete()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Category.php`](../src/Resource/App/Category.php)

## Tests

- [`tests/Resource/App/CacheTest.php`](../tests/Resource/App/CacheTest.php)

## Key points

`#[Purge(uri: 'app://self/articles')]` をwrite methodに付ける。`#[Purge]` はrepeatableで、URI templateにmethod引数をbindできる（`#[Purge(uri: 'app://self/user/friend?user_id={id}')]`）。非 `#[Cacheable]` クラスでは `#[Purge]`/`#[Refresh]` 付きmethodのみにinterceptorがbindされるため、write methodごとの付け忘れに注意。実行時の手動無効化は `DonutRepositoryInterface::purge(new Uri(...))` / `invalidateTags([...])` でも行える（manual「Cache invalidation」）。

## Do not

- `#[Purge]` にitem URIを渡さない — write methodが属するresource自身（同一URI）の無効化は自動で行われる。手動purgeが要るのは別URIのcollection cacheだけ。

## マスター確認（After）

- [ ] POST/PUT/DELETE後にRepositoryLoggerのログへ `purge-query-repository` と対象collection URIが出ることを `CacheTest.php` 相当で green。

## See also

- [`cacheable-response`](./cacheable-response.md) — purge対象のcollectionが持つclass-levelのcache宣言（`#[CacheableResponse]`）
- [`cacheable-leaf`](./cacheable-leaf.md) — `#[Cacheable]` 単体のleaf。同一URIのwriteは自動refreshされ手動purge不要
- [`cache-embed-dependency`](./cache-embed-dependency.md) — `#[Embed]` 依存による自動無効化（手動purgeの対）
- [`donut-cache`](./donut-cache.md) — `#[DonutCache]` の部分キャッシュ
- [`conditional-request-304`](./conditional-request-304.md) — ETagで304を返す
- [`db-command-write`](./db-command-write.md) — purgeを引き起こすwrite側（Command）の型
