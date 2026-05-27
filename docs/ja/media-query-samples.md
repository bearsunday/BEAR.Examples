# Ray.MediaQuery サンプル

[English](../media-query-samples.md)

このプロジェクトでは、Ray.MediaQuery の dispatch shape が framework 内部を読まなくても
分かるように、小さなサンプルをコードベースに残しています。

## Pager

`ArticleQueryInterface::list()` は `#[Pager]` 経路のサンプルです。

- [src/Query/ArticleQueryInterface.php](../../src/Query/ArticleQueryInterface.php)
- [src/Resource/App/Articles.php](../../src/Resource/App/Articles.php)
- [tests/Fake/FakePages.php](../../tests/Fake/FakePages.php)

Query は `PagesInterface` を返します。Resource code は `$pages[$page]` を読み、
返された Page object の `data`、`total`、`hasNext`、`maxPerPage` を使います。
Fake 実装は Pagerfanta の `ArrayAdapter` を使うので、DB なしテストでも同じ
shape を検証できます。

`#[DbQuery(factory: ...)]` は `#[Pager]` と組み合わせることもでき、その場合
Page data はあらかじめ hydrate されます。このサンプルでは意図的に
`ArticleQueryInterface::list()` を raw rows のままにし、Resource で
`ArticleFactory::fromRows()` によって hydrate しています。Pager の `data`
contract と Resource boundary を同じ場所で見せるためです。

範囲外 page の clamp は、`$pages[$page]` を読む前に `count($pages)` を計算します。
production では clamp 用の COUNT と Pagerfanta が Page を読むときの COUNT が
それぞれ発行される可能性があります。reference では
`OutOfRangeCurrentPageException` を catch する形より読みやすい直線的な形を
優先しています。COUNT cost が目に見える場合だけ再検討します。

## SELECT Result Class

`ArticleSelectionQueryInterface::list()` は MediaQuery 1.1 の SELECT
`PostQueryInterface` 経路のサンプルです。

- [src/Query/ArticleSelectionQueryInterface.php](../../src/Query/ArticleSelectionQueryInterface.php)
- [src/Result/ArticleSelection.php](../../src/Result/ArticleSelection.php)
- [var/db/sql/article_selection_list.sql](../../var/db/sql/article_selection_list.sql)

`src/Result/*` は `src/Query/*Interface` method から返される型付き
Ray.MediaQuery result の置き場です。これらは domain entity ではなく、query
execution context や DML metadata を包む object です。`src/Query` と
`src/Result` が明示的な対として読めるよう、このディレクトリは query result
専用に保ちます。

read query では、これらの result object は query-local projection として読みます。
つまり、特定の `#[DbQuery]` 結果から組み立てる型付き read-side view であり、
domain entity method や controller / service helper ではありません。

`#[DbQuery]` method は array ではなく `ArticleSelection` を返します。MediaQuery は
`ArticleFactory` で row を hydrate し、`PostQueryContext::$rows` に hydrated
`Article` rows を入れ、`ArticleSelection::fromContext()` を呼びます。

`ArticleSelection::published()` は basic な iterator 例です。戻り値は
`Generator` で、published な `Article` row だけを yield します。caller は
template に status 判定を書く代わりに、名前付き traversal を選べます。

```php
foreach ($articles->published() as $article) {
    // published Article を描画する
}
```

`page://self/articlefeed` は CQRS projection の応用例です。同じ SQL に基づく
`ArticleSelection` を使いますが、`ArticleSelection::feed()` は `Article`
entity ではなく `ArticleFeedItem` read model を yield します。feed item は
この表示関心のためだけの使い捨て query-side projection で、article URL、
summary、published date label、`5 minutes ago` のような relative
`postedAgoLabel` を持ちます。

template は描画だけに集中できます。

```php
foreach ($articles->feed() as $item) {
    // $item は Article ではなく ArticleFeedItem
}
```

目的は `src/Resource/App/Variations/` route を増やすことではありません。あの
route 群は Article GET の実装スタイル比較用です。`ArticleFeed` は、同じ rows
に対して別の read concern があれば、別の query-side projection を作れることを
示します。

## Affected Rows

`ArticleAffectedRowsCommandInterface` は DML metadata result のサンプルです。

- [src/Query/Samples/ArticleAffectedRowsCommandInterface.php](../../src/Query/Samples/ArticleAffectedRowsCommandInterface.php)
- [tests/Smoke/MediaQuerySamplesTest.php](../../tests/Smoke/MediaQuerySamplesTest.php)

canonical な Resource 用 Command interface は引き続き `void` を返します。Resource
では通常、UPDATE / DELETE の前に 404 を判定するからです。write metadata が必要な
caller では、`#[DbQuery]` method の return type に `AffectedRows` を宣言し、
`$result->count` または `$result->isAffected()` を使います。
