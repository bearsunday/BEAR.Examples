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

`#[DbQuery(factory: ...)]` can also be combined with `#[Pager]` so Page data is
already hydrated. This sample intentionally leaves `ArticleQueryInterface::list()`
as raw rows and hydrates in the Resource with `ArticleFactory::fromRows()`, making
the Pager `data` contract visible alongside the Resource boundary.

The out-of-range page clamp computes `count($pages)` before reading
`$pages[$page]`. In production that may issue one COUNT query for the clamp and
another through Pagerfanta when the Page is read. The reference keeps this
straight-line form because it is easier to read than catching
`OutOfRangeCurrentPageException`; revisit it only if COUNT cost becomes visible.

## SELECT Result Class

`ArticleSelectionQueryInterface::list()` demonstrates the MediaQuery 1.1 SELECT
`PostQueryInterface` path:

- [src/Query/ArticleSelectionQueryInterface.php](../src/Query/ArticleSelectionQueryInterface.php)
- [src/Result/ArticleSelection.php](../src/Result/ArticleSelection.php)
- [var/db/sql/article_selection_list.sql](../var/db/sql/article_selection_list.sql)

`src/Result/*` is reserved for typed Ray.MediaQuery results returned by
`src/Query/*Interface` methods. These classes are not domain entities; they
wrap query execution context or DML metadata while keeping `src/Query` and
`src/Result` as an explicit pair.

For read queries, read these result objects as query-local projections: typed
read-side views assembled from a specific `#[DbQuery]` result, without turning
the behavior into domain entity methods or controller/service helpers.

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
