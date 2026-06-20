---
layout: default
title: "BDRスライス: Bound / Domain / Resource"
permalink: /ja/catalog/bdr-slice/
---

<article class="sampleCard" markdown="1">

# BDRスライス: Bound / Domain / Resource

英語版: [`/samples/bdr-slice/`]({{ '/samples/bdr-slice/' | relative_url }})

<section class="intent" markdown="1">

## 意図

MyVendor.Cms の正規の縦断スライスを示します。URI は Bound で受け、Domain の不変データ形に写し、Resource の問い合わせ境界から SQL へ進みます。重要なのは略語ではなく、各層の責務が小さく、ソースとテストで追えることです。

</section>

<section class="summary" markdown="1">

## BDRの3層

- **Bound**: `src/Resource/App/*`。URI、HTTP メソッド、リンク、埋め込み、検証、ステータスコードの境界です。
- **Domain**: `src/Entity/*`。`final readonly` な不変データ形です。`SELECT` のカラム順とコンストラクタ順を合わせます。
- **Resource**: `src/Query/*Interface` と `var/db/sql/*`。Ray.MediaQuery の問い合わせ境界です。読み取りは `QueryInterface`、書き込みは `CommandInterface` に分けます。

</section>

<section class="useWhen" markdown="1">

## 使う時

- 新しい App URI を追加するとき。
- 処理を Resource、Entity、Query/Command、SQL のどこに置くか迷ったとき。
- AI エージェントに、このプロジェクトの形を先に読ませてから小さなパターンをコピーさせたいとき。

</section>

<section class="doNotUseWhen" markdown="1">

## 使わない時

- 既存メソッド内の小さな差分だけで済むとき。
- `src/Resource/App/Variations/` の比較専用実装を説明するとき。
- 慣れた名前だからという理由で、未検証のサービス層やリポジトリ層に横断処理を隠したいとき。

</section>

<section class="patternDiff" markdown="1">

## 正規の書き換え形

これは履歴の差分ではなく、このコードベースで選ぶ形を示す差分です。

```diff
- Controller
-   -> Repository
-   -> array row
-   -> ad-hoc response
+ Bound:    src/Resource/App/Article.php
+ Domain:   src/Entity/Article.php
+ Resource: src/Query/ArticleQueryInterface.php
+ Resource: src/Query/ArticleCommandInterface.php
+ SQL:      var/db/sql/article_item.sql
```

</section>

<section class="shapeExcerpt" markdown="1">

## 形の抜粋

```php
final class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleCommandInterface $articleCmd,
    ) {}

    public function onGet(int $id): static
    {
        $article = $this->article->item($id);

        $this->body = $article === null
            ? ['message' => 'Article not found']
            : [
                'id' => $article->id,
                'slug' => $article->slug,
                'title' => $article->title,
            ];

        return $this;
    }
}

final readonly class Article
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
    ) {}
}

interface ArticleQueryInterface
{
    #[DbQuery('article_item')]
    public function item(int $id): Article|null;
}
```

</section>

<section class="notes" markdown="1">

## 注意

- `Bound` には BEAR.Resource の属性、検証、リンク、埋め込み、ステータスコードを置きます。
- `Domain` は問い合わせ結果から水和される不変データ形です。
- BDR の `Resource` は MediaQuery 境界を指します。App Resource と混同しないでください。
- SQL ファイルもスライスの一部です。メソッド名、`#[DbQuery]` の ID、SQL ファイル名を対応させます。
- テストは、モックで横道を作らず、Fake または実 DB 経由で同じ Resource コードを動かします。

</section>

## リンク

- ソース: [`src/Resource/App/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Article.php){: .goSource }
- エンティティ: [`src/Entity/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Entity/Article.php){: .goSource }
- クエリ: [`src/Query/ArticleQueryInterface.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Query/ArticleQueryInterface.php){: .goSource }
- コマンド: [`src/Query/ArticleCommandInterface.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Query/ArticleCommandInterface.php){: .goSource }
- SQL: [`var/db/sql/article_item.sql`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/var/db/sql/article_item.sql){: .goSource }
- テスト: [`tests/Resource/App/ArticleTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/ArticleTest.php){: .goTest }
- スモークテスト: [`tests/Smoke/MediaQuerySmokeTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Smoke/MediaQuerySmokeTest.php){: .goTest }
- 規約: [`docs/ja/architecture.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/ja/architecture.md#bdr-pattern-bound--domain--resource){: .goConvention }
- 規約: [`docs/ja/conventions.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/ja/conventions.md){: .goConvention }

</article>
