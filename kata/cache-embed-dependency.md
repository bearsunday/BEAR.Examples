# `cache-embed-dependency`

**`#[Embed]` 親Resourceの依存を自動合成する** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** cache parent, embed dependency, auto dependency, ETag dependency, AuthorProfile, キャッシュ依存, 依存解決, タグベース無効化
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** 親Resourceが子Resourceをembedし、子の更新で親cacheも無効化したい。

## 例

### 親Resource — `#[Cacheable]` + `#[Embed]`

合成は `#[Embed]` の宣言だけ — cache依存の手書きコードは無い。`$this->body['author']` にはEmbed Requestが入っているので `+=` で追記する（`=` で潰さない）。子が見つからない時は `$this->body` を丸ごと置き換えてEmbed Requestを落とし、404を返す:

```php
#[Cacheable]
class AuthorProfile extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    #[Embed(rel: 'author', src: 'app://self/cache/author')]
    public function onGet(int $authorId): static
    {
        if ($this->author->item($authorId) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $authorId];

            return $this;
        }

        $this->body['author']->addQuery(['id' => $authorId]);

        $this->body += [
            'authorId' => $authorId,
            'dependencyUri' => 'app://self/cache/author?id=' . $authorId,
            'cachePattern' => 'Cacheable + Embed (auto dependency)',
        ];

        return $this;
    }
}
```

### 子Resource（依存先leaf）

子は素の `#[Cacheable]` leaf（→ [`cacheable-leaf`](./cacheable-leaf.md)）。cache primitiveはclassに一切現れず、writeはdefaultの `RefreshSameCommand` が自動purgeする — 親のSurrogate-Keyに子URI tagがmergeされているため、親cacheも連鎖して無効化される:

```php
#[Cacheable]
class Author extends ResourceObject
{
    public function onPut(int $id, string $name, string $email, string $bio = ''): static
    {
        if ($this->author->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $id];

            return $this;
        }

        $this->authorCmd->update($id, $name, $email, $bio);
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

`#[Embed]` の rel は ALPS Taxonomy 層のエンティティ名詞 — Choreography の遷移動詞と混ぜない:

| Where | Source layer | 例 |
|---|---|---|
| `#[Link]` rel | Choreography（遷移動詞） | `goAuthor`, `doUpdateCacheAuthor` |
| `#[Embed]` rel | Taxonomy（エンティティ名詞） | `author`, `category`, `tagList` |

`#[Embed(rel: 'goAuthor', ...)]` は誤り — embed はserverが含めるtaxonomyインスタンスであり、clientが辿る遷移ではない。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 単一の子依存は `#[Embed]` で表し、`fromAssoc()` を使わないと決めたか。
- [ ] 親はmanual cache codeを持たず、QueryRepositoryが子URI tagを親へmergeする前提を理解したか。
- [ ] demo/testでは `CacheShowcaseModule`（ArrayAdapter override）を重ねると理解したか。

## Source

- [`src/Resource/App/Cache/AuthorProfile.php`](../src/Resource/App/Cache/AuthorProfile.php)
- [`src/Resource/App/Cache/Author.php`](../src/Resource/App/Cache/Author.php)
- [`src/Module/CacheShowcaseModule.php`](../src/Module/CacheShowcaseModule.php)

## Tests

- [`tests/Resource/App/Cache/AuthorProfileCacheTest.php`](../tests/Resource/App/Cache/AuthorProfileCacheTest.php)

## Key points

`#[Embed]` 子のURI tagをQueryRepositoryが親へmergeする。親Resourceはmanual cache codeを持たない。子が見つからない時は `$this->body` を丸ごと置き換えてEmbed Requestを落として404を返す — `CacheInterceptor` はcode 200のみ保存するため404がstale cacheにならない。

## Do not

- `#[Embed]` で表せる単一子依存に `fromAssoc()` を使わない。`fromAssoc()`・`Header::SURROGATE_KEY` 代入・手動 `$this->resource->get(...)` が親に現れたら型崩れ — `AuthorProfileCacheTest::testSourceHasNoManualCacheCode()` がこれをreflectionで落とす。

## マスター確認（After）

- [ ] 親Resourceが `#[Embed]` で子を持ち、cache依存の手書きコードが無い。
- [ ] 親レスポンスの `Surrogate-Key` に子URI tagが含まれる。
- [ ] 子の更新で親cacheが無効化されることを `AuthorProfileCacheTest.php` 相当で green。

## See also

- [`cacheable-leaf`](./cacheable-leaf.md) — 子側の型（`#[Cacheable]` 単体のleaf）
- [`cache-body-derived-dependency`](./cache-body-derived-dependency.md) — `#[Embed]` で表せない複数依存をbodyから導出する
- [`hal-embed`](./hal-embed.md) — `#[Embed]` 合成の基本形
- [`cache-purge`](./cache-purge.md) — write側からの明示的なpurge
- [`conditional-request-304`](./conditional-request-304.md) — ETagで304を返す
- [`not-found-response`](./not-found-response.md) — 404のidiom
