# `async-embed-parallel`

**embed graphを並列実行に載せる** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** async, parallel embed, BEAR.Async, ext-parallel, Swoole, embedded resources, 並列実行, 非同期, 並列化
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/async.html
- **Use when:** 既存Resourceの `#[Embed]` graphを変更せずに、runtime overlayで並列化したい。

## 例

### Resource（変更しない）

Resource は通常の `#[Embed]` のまま — sync版と並列版で差分ゼロ:

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
        // ...
    ];

    return $this;
}
```

### 並列entrypoint

`bin/async.php` は `bear/async` の bootstrap に差し替えるだけ。並列化は runtime 側の overlay:

```php
$bootstrap = dirname(__DIR__) . '/vendor/bear/async/bootstrap.php';

$defaultContext = PHP_SAPI === 'cli' ? 'cli-hal-api-app' : 'hal-api-app';
$context = getenv('APP_CONTEXT') ?: $defaultContext;

exit((require $bootstrap)(
    $context,
    'BEAR\Kata',
    dirname(__DIR__),
    $GLOBALS,
    $_SERVER,
));
```

### 実行環境（ZTS + ext-parallel container）

実行には ext-parallel + ZTS PHP が要る。`docker-compose.yml` の `parallel` serviceが提供する:

```yaml
  parallel:
    build:
      context: .
      dockerfile: docker/ext-parallel/Dockerfile
    depends_on:
      mysql:
        condition: service_healthy
```

実行手順:

```bash
composer parallel:up    # ZTS + ext-parallel container起動 + MySQL migrate/seed
composer parallel:demo  # container内で php bin/async.php get 'app://self/article?id=1'
```

## Naming

並列実行される単位は `#[Embed]` の子。rel 命名は ALPS の layer で分ける:

| Where | Source layer | 例 |
|---|---|---|
| `#[Embed]` rel | ALPS Taxonomy（entity名詞） | `author`, `category`, `tagList` |
| `#[Link]` rel | ALPS Choreography（遷移動詞） | `goArticleList`, `goAuthor` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] Resource code は通常の `#[Embed]` のまま変えず、並列化は runtime/context 側のmodule overlayで行うと理解したか。
- [ ] 実行に ext-parallel + ZTS PHP（または Swoole）が必要な前提を確認したか（BEAR.AsyncはmanualでAlpha表記）。
- [ ] 並列実行される `#[Embed]` 子はread-only（冪等GET）で順序依存が無く、thread boundaryを跨ぐ値はcopyable（scalar/null/そのnested array）のみと確認したか。各workerは独立したDI containerを持つ。

## Source

- [`bin/async.php`](../bin/async.php)
- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`docker-compose.yml`](../docker-compose.yml)

## Tests

- [`tests/Hypermedia/HalEnvelopeContractTest.php`](../tests/Hypermedia/HalEnvelopeContractTest.php)
- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)

## Key points

Resource codeは通常の `#[Embed]` のまま。runtime/context側でparallel moduleを重ねる。実行は `composer parallel:up`（ZTS + ext-parallel container起動）→ `composer parallel:demo`。Swooleは別entrypointではなく `AsyncSwooleModule` + connection poolをAppModuleにinstallする形（本リポジトリはDockerfileのみ用意、module wiringは未着手）。

## Do not

- 並列化のためにResource body assemblyを別物に書き換えない。並列化はruntime overlayの責務で、sync版とResourceクラスの差分はゼロに保つ。

## マスター確認（After）

- [ ] sync版と並列版で Resource クラスの差分がゼロ（並列化はcontext側のみ）。
- [ ] 並列版でも同じHAL envelopeが出ることを `HalEnvelopeContractTest.php` 相当で green。

## See also

- [`hal-embed`](./hal-embed.md) — 並列化の対象になる `#[Embed]` graph の作り方
- [`api-get-hal-resource`](./api-get-hal-resource.md) — 並列版でも不変であるべき HAL envelope の基本形
- [`cache-embed-dependency`](./cache-embed-dependency.md) — 同じ `#[Embed]` graph をcache依存伝播に使う
- [`defer-resource-request`](./defer-resource-request.md) — 実行を遅延させるもう1つの runtime overlay
- [`cli-resource`](./cli-resource.md) — 同じ Resource を別 runtime（CLI）に載せる
