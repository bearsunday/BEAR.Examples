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

## SELECT Result Class

`ArticleSelectionQueryInterface::list()` は MediaQuery 1.1 の SELECT
`PostQueryInterface` 経路のサンプルです。

- [src/Query/ArticleSelectionQueryInterface.php](../../src/Query/ArticleSelectionQueryInterface.php)
- [src/Result/ArticleSelection.php](../../src/Result/ArticleSelection.php)
- [var/db/sql/article_selection_list.sql](../../var/db/sql/article_selection_list.sql)

`#[DbQuery]` method は array ではなく `ArticleSelection` を返します。MediaQuery は
`ArticleFactory` で row を hydrate し、`PostQueryContext::$rows` に hydrated
`Article` rows を入れ、`ArticleSelection::fromContext()` を呼びます。

## Affected Rows

`ArticleAffectedRowsCommandInterface` は DML metadata result のサンプルです。

- [src/Query/Samples/ArticleAffectedRowsCommandInterface.php](../../src/Query/Samples/ArticleAffectedRowsCommandInterface.php)
- [tests/Smoke/MediaQuerySamplesTest.php](../../tests/Smoke/MediaQuerySamplesTest.php)

canonical な Resource 用 Command interface は引き続き `void` を返します。Resource
では通常、UPDATE / DELETE の前に 404 を判定するからです。write metadata が必要な
caller では、`#[DbQuery]` method の return type に `AffectedRows` を宣言し、
`$result->count` または `$result->isAffected()` を使います。

