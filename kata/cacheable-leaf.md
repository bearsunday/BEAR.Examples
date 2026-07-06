# `cacheable-leaf`

**`#[Cacheable]` だけのleaf resourceを作る** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** cache leaf, `#[Cacheable]`, self URI tag, auto purge, QueryRepository cache, RefreshSameCommand, キャッシュ, 自動無効化, タグベース無効化
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cache.html
- **Use when:** 単体ResourceのGET/PUTで、利用者コードなしにキャッシュと同一URI purgeを示したい。

## 例

### Resource

cache surfaceはclass-levelの `#[Cacheable]` のみ。method本体は素のread/write（[`db-read-one-entity`](./db-read-one-entity.md) / [`db-command-write`](./db-command-write.md) と同じ形）で、cache primitiveは一切現れない。保存時のself URI tag書き込みも、PUT後の同一URI purge（`RefreshSameCommand`）もframeworkが自動で行う:

```php
use BEAR\RepositoryModule\Annotation\Cacheable;

#[Cacheable]
class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
        private readonly AuthorCommandInterface $authorCmd,
    ) {
    }

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

`Cache\Tag` も同じ形（対称なleaf）。

### Module（demo/test用のcache adapter override）

非prod contextのcache adapterはNullAdapter。showcase/testではArrayAdapterをoverrideしてin-memory cacheを有効にする:

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

### Test

`Injector::getOverrideInstance` でModuleを重ね、repeated GETが同一 `ETag` を返すことを検証する:

```php
$injector = Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule());
$this->resource = $injector->getInstance(ResourceInterface::class);
$this->httpCache = $injector->getInstance(HttpCacheInterface::class);

$first = $this->resource->get('app://self/cache/author', ['id' => 1]);
$second = $this->resource->get('app://self/cache/author', ['id' => 1]);

$this->assertSame($first->headers[Header::ETAG], $second->headers[Header::ETAG]);
$this->assertTrue($this->httpCache->isNotModified([
    Header::HTTP_IF_NONE_MATCH => $first->headers[Header::ETAG],
]));
```

PUTで同一URIがpurgeされ、次のGETで `ETag` が変わる:

```php
$oldEtag = $first->headers[Header::ETAG];

$this->resource->put('app://self/cache/author', [
    'id' => 1,
    'name' => 'Edited Author',
    'email' => 'edited.author@example.com',
    'bio' => 'Edited via Cache\\Author PUT.',
]);

$second = $this->resource->get('app://self/cache/author', ['id' => 1]);
$this->assertNotSame($oldEtag, $second->headers[Header::ETAG]);
```

user-zero-code invariantはreflectionでpinする — sourceにcache primitiveが現れたらred:

```php
$src = file_get_contents((new ReflectionClass(Author::class))->getFileName());
foreach (['Header::SURROGATE_KEY', 'UriTagInterface', 'DonutRepositoryInterface', 'invalidateTags', 'fromAssoc'] as $needle) {
    $this->assertStringNotContainsString($needle, $src);
}
```

## Naming

cache showcaseのResourceは正規CMS Resourceとは別に `Cache/` 配下に置く。依存プロパティの命名は通常のResourceと同じ:

| 種別 | 形 | 例 |
|---|---|---|
| showcase Resource | `src/Resource/App/Cache/<Entity>.php`（URI `app://self/cache/<entity>`） | `Cache\Author`, `Cache\Tag` |
| read依存 | `$<entity>`（`<Entity>QueryInterface`） | `private AuthorQueryInterface $author` |
| write依存 | `$<entity>Cmd`（`<Entity>CommandInterface`） | `private AuthorCommandInterface $authorCmd` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] cache surface を class-level `#[Cacheable]` だけで表し、manual cache primitive をResourceに出さないと決めたか。
- [ ] 非prod contextのcache adapterはNullAdapterのため、demo/testでは `Injector::getOverrideInstance('test-hal-api-app', new CacheShowcaseModule())`（ArrayAdapter override）を重ねると理解したか。

## Source

- [`src/Resource/App/Cache/Author.php`](../src/Resource/App/Cache/Author.php)
- [`src/Resource/App/Cache/Tag.php`](../src/Resource/App/Cache/Tag.php)
- [`src/Module/CacheShowcaseModule.php`](../src/Module/CacheShowcaseModule.php)

## Tests

- [`tests/Resource/App/Cache/AuthorCacheTest.php`](../tests/Resource/App/Cache/AuthorCacheTest.php)

## Key points

class-level `#[Cacheable]` がcache surface。manual cache primitiveはResourceに出さない。デフォルトはevent-driven無効化（TTL無期限）で、TTLが必要なら `#[Cacheable(expirySecond: 30)]` / `#[Cacheable(expiryAt: 'expiry_at')]` を指定できる。

## Do not

- leaf resourceに不要な `Surrogate-Key` 手書きコードを足さない — self URI tagはframeworkが保存時に書き、write時のpurgeも `RefreshSameCommand` が行う。手書きすると `QueryRepository::setCacheDependency()` が「Surrogate-Key既設」とみなして早期returnし、frameworkの自動依存解決をスキップしてしまう。

## マスター確認（After）

- [ ] class に `#[Cacheable]` があり、sourceに `Surrogate-Key` / `UriTagInterface` / `fromAssoc` 等のcache primitiveが現れない（reflectionで pin）。
- [ ] repeated GET が同一 `ETag` を返し、PUT後にGETが更新され同一URIがpurgeされることを `AuthorCacheTest.php` 相当で green。

## See also

- [`cache-embed-dependency`](./cache-embed-dependency.md) — `#[Embed]` 親が子leafのtagを自動合成する（Shape A）
- [`cache-body-derived-dependency`](./cache-body-derived-dependency.md) — body由来の可変長依存を `fromAssoc()` で宣言する（Shape B）
- [`cache-purge`](./cache-purge.md) — `#[Purge]` でwrite時に別URI（collection）を無効化する
- [`donut-cache`](./donut-cache.md) — `#[DonutCache]` の部分キャッシュ
- [`cacheable-response`](./cacheable-response.md) — `#[CacheableResponse]` でレスポンス全体をキャッシュする
- [`conditional-request-304`](./conditional-request-304.md) — `ETag` を使った条件付きリクエスト（304）
- [`db-read-one-entity`](./db-read-one-entity.md) — leafの `onGet` の読み取り自体の型
