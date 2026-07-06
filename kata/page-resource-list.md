# `page-resource-list`

**Page Resourceで一覧HTMLを描画する** · [← 索引に戻る](../index.md)

- **Category:** HTML / Page
- **Status:** `canonical`
- **Aliases:** HTML list, page list, Qiq list, pager HTML, filter page, 一覧ページ, ページング, 絞り込み, published限定
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/html-qiq.html
- **Use when:** 絞り込みやpager付きの一覧HTMLを表示したい。

## 例

### Page Resource（filter + pager）

範囲外入力のclamp、publishedの強制、pager表示値の抽出をすべてResource側で行う。templateに渡すのは表示するだけの値:

```php
private const int DEFAULT_PER_PAGE = 10;
private const int MAX_PER_PAGE = 100;

public function onGet(
    int|null $categoryId = null,
    int|null $tagId = null,
    int|null $authorId = null,
    string|null $status = null,
    int $page = 1,
    int $perPage = self::DEFAULT_PER_PAGE,
): static {
    $page = max(1, $page);
    $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
    $publicStatus = ArticleStatus::Published->value;
    $pages = $this->article->list(
        categoryId: $categoryId,
        tagId: $tagId,
        authorId: $authorId,
        status: $publicStatus,
        perPage: $perPage,
    );
    $totalPages = max(1, (int) ceil(count($pages) / $perPage));
    $page = min($page, $totalPages);
    $articlePage = $pages[$page];
    assert($articlePage instanceof PagerPage);
    $rows = $articlePage->data;
    $articles = $this->articleFactory->fromRows($rows);

    $this->body = [
        'articles' => $articles,
        'filter' => [
            'categoryId' => $categoryId,
            'tagId' => $tagId,
            'authorId' => $authorId,
            'status' => $status === $publicStatus ? $publicStatus : null,
        ],
        'category' => $categoryId === null ? null : $this->category->item($categoryId),
        'tag' => $tagId === null ? null : $this->tag->item($tagId),
        'author' => $authorId === null ? null : $this->author->item($authorId),
        'page' => $articlePage->current,
        'perPage' => $articlePage->maxPerPage,
        'hasNext' => $articlePage->hasNext,
    ];

    return $this;
}
```

### Template（Qiq）

templateは受け取った値をloopして表示するだけ:

```php
<?php foreach ($articles as $article): ?>
  <article class="Article">
    <h2 class="title">
      <a class="goArticle" href="/article?id={{h $article->id }}">{{h $article->title }}</a>
    </h2>
    <span class="status" data-status="{{h $article->status->value }}">{{h $article->status->value }}</span>
  </article>
<?php endforeach ?>
```

pagerリンクは `$page` / `$hasNext` で出し分ける。filter状態を引き継いだ `$prevUrl` / `$nextUrl` はtemplate冒頭の `$qs` ヘルパ（null除去 + `http_build_query`）で組み立てる:

```php
<nav class="Pagination">
  <ul>
    <?php if ($page > 1): ?>
      <li><a class="goPrev" {{a ['href' => $prevUrl] }}>Previous page</a></li>
    <?php endif ?>
    <?php if ($hasNext): ?>
      <li><a class="goNext" {{a ['href' => $nextUrl] }}>Next page</a></li>
    <?php endif ?>
  </ul>
</nav>
```

### 最小形（filter / pagerなし）

`CategoryList` / `TagList` は `list()` の結果をbodyに入れるだけの下限例:

```php
/** @property array{categories: list<Category>} $body */
class CategoryList extends ResourceObject
{
    public function __construct(
        private readonly CategoryQueryInterface $category,
    ) {
    }

    public function onGet(): static
    {
        $this->body = ['categories' => $this->category->list()];

        return $this;
    }
}
```

## Naming

Page Resource と template は同名で対にする:

| 対象 | 命名 | 例 |
|---|---|---|
| Page Resource | `src/Resource/Page/<Name>.php`（URIは小文字） | `ArticleList` → `page://self/articlelist` |
| Template | `templates/Page/<Name>.php`（Resourceと同名） | `templates/Page/ArticleList.php` |
| 一覧 query method | `list()` / `list<Variant>()` | `article_list.sql` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] list query・filter状態・pager表示用の値を Page Resource 側で準備すると決めたか。
- [ ] template側でDB fetchやfilter解決をしないと理解したか。
- [ ] `page` / `perPage` の範囲外入力をResource側でclampすると決めたか。

## Source

- [`src/Resource/Page/ArticleList.php::onGet()`](../src/Resource/Page/ArticleList.php)
- [`templates/Page/ArticleList.php`](../templates/Page/ArticleList.php)
- [`src/Resource/Page/CategoryList.php::onGet()`](../src/Resource/Page/CategoryList.php)
- [`src/Resource/Page/TagList.php::onGet()`](../src/Resource/Page/TagList.php)

## Tests

- [`tests/Resource/Page/ArticleListTest.php`](../tests/Resource/Page/ArticleListTest.php)
- [`tests/Resource/Page/CategoryListTest.php`](../tests/Resource/Page/CategoryListTest.php)
- [`tests/Resource/Page/TagListTest.php`](../tests/Resource/Page/TagListTest.php)

## Key points

list query、filter状態、pager表示用値をPage Resourceで準備する。public一覧はuser入力の `status` を無視してserver側でpublishedを強制する（`ArticleList::onGet()` は常に `status: ArticleStatus::Published->value` で `list()` を呼ぶ）。pager表示値は `Page` の `current` / `maxPerPage` / `hasNext` から作る。`CategoryList` / `TagList` はfilter/pagerなしの最小形（一覧Kataの下限例）。

## マスター確認（After）

- [ ] template に DB fetch / filter解決ロジックが無い。
- [ ] public一覧は published のみに制限され、status filter入力でdraftを露出できないことを `ArticleListTest.php` 相当で green。
- [ ] filter適用の一覧を `ArticleListTest.php` 相当で green。

## See also

- [`page-resource-qiq-detail`](./page-resource-qiq-detail.md) — 1件詳細をQiqで描画する対の型
- [`db-read-list-pager`](./db-read-list-pager.md) — `#[Pager]` 付きlist query（読み側）の型
- [`db-read-one-entity`](./db-read-one-entity.md) — filter表示用Entityを `item()` で引く
- [`page-resource-test`](./page-resource-test.md) — Page HTMLを `toString()` で検証するテストの型
