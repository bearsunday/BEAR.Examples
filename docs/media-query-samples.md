# Ray.MediaQuery Samples

[日本語](ja/media-query-samples.md)

This project keeps a few small Ray.MediaQuery examples in the codebase so the
dispatch shapes are visible without reading the framework internals.

## Pager

`ArticleQueryInterface::list()` demonstrates the `#[Pager]` path:

- [src/Query/ArticleQueryInterface.php](../src/Query/ArticleQueryInterface.php)
- [src/Resource/App/Articles.php](../src/Resource/App/Articles.php)
- [tests/Fake/FakePages.php](../tests/Fake/FakePages.php)

The query returns `PagesInterface`. Resource code reads `$pages[$page]`, then
uses the returned Page object's `data`, `total`, `hasNext`, and `maxPerPage`
fields. The fake implementation uses Pagerfanta's `ArrayAdapter`, so DB-free
tests exercise the same shape.

## SELECT Result Class

`ArticleSelectionQueryInterface::list()` demonstrates the MediaQuery 1.1 SELECT
`PostQueryInterface` path:

- [src/Query/ArticleSelectionQueryInterface.php](../src/Query/ArticleSelectionQueryInterface.php)
- [src/Result/ArticleSelection.php](../src/Result/ArticleSelection.php)
- [var/db/sql/article_selection_list.sql](../var/db/sql/article_selection_list.sql)

The `#[DbQuery]` method returns `ArticleSelection`, not an array. MediaQuery
hydrates rows through `ArticleFactory`, puts the hydrated `Article` rows in
`PostQueryContext::$rows`, and calls `ArticleSelection::fromContext()`.

## Affected Rows

`ArticleAffectedRowsCommandInterface` demonstrates DML metadata results:

- [src/Query/Samples/ArticleAffectedRowsCommandInterface.php](../src/Query/Samples/ArticleAffectedRowsCommandInterface.php)
- [tests/Smoke/MediaQuerySamplesTest.php](../tests/Smoke/MediaQuerySamplesTest.php)

The canonical resource command interface still returns `void`; resources usually
decide 404 before issuing UPDATE / DELETE. When a caller does need write
metadata, declare `AffectedRows` as the return type of a `#[DbQuery]` method and
use `$result->count` or `$result->isAffected()`.
