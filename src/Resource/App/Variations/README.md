# Article GET Variations

[日本語](README.ja.md)

This directory contains comparison-only implementations of the Article GET
resource. The canonical endpoint is `src/Resource/App/Article.php`; these
classes are reading material for understanding the tradeoffs.

These classes are not competing API designs. They are a controlled reading
guide: the response shape is intentionally similar, so the differences show
where responsibility moves when you change one axis at a time.

Run the demo with:

```bash
composer demo:variations
```

## Reading Guide

Read for three things:

- Where the Article invariant lives: entity, Resource, SQL row, or DB access.
- How much response shaping the Resource must do itself.
- What MediaQuery removes from the Resource: SQL lookup, parameter binding,
  fetch mode, and query naming.

## Highlights

| Class | Focus | What it shows |
|---|---|---|
| `ArticleAsArray` | Entity vs array | Shows the smallest non-entity version. Notice the explicit array shape, casts, datetime normalization, and how domain meaning is no longer carried by an `Article` object. |
| `ArticleSqlQuery` | Declarative query vs programmatic query orchestration | Shows the point where one `#[DbQuery]` call is no longer the whole story. Reading time and previous/next navigation make the Resource coordinate multiple query results. |
| `ArticleRawPdo` | MediaQuery vs raw PDO | Shows the database path with the framework help removed. The SQL is visible in the class, and so are the concerns MediaQuery normally hides. |

## Reading Order

1. Start with `ArticleAsArray` to see the smallest possible Article response.
2. Read `ArticleSqlQuery` to see where a resource starts coordinating multiple
   query results.
3. Read `ArticleRawPdo` last to compare the same response shape without
   MediaQuery.
