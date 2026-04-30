# Article GET Variations

[日本語](README.ja.md)

This directory contains comparison-only implementations of the Article GET
resource. The canonical endpoint is `src/Resource/App/Article.php`; these
classes are reading material for understanding the tradeoffs.

Run the demo with:

```bash
composer demo:variations
```

## What To Look For

| Class | Focus | What it shows |
|---|---|---|
| `ArticleAsArray` | Entity vs array | Uses `#[DbQuery]` and maps the row directly into a response array. This is short, but response shaping, type casts, and invariants stay inside the resource. |
| `ArticleSqlQuery` | Declarative query vs programmatic query orchestration | Uses `SqlQueryInterface` when the resource needs multiple reads and PHP-side work such as reading time and previous/next navigation. |
| `ArticleRawPdo` | MediaQuery vs raw PDO | Runs explicit SQL through `ExtendedPdoInterface`. This makes the database path visible, while also showing what MediaQuery normally centralizes: SQL discovery, binding, fetch strategy, and query naming. |

## Reading Order

1. Start with `ArticleAsArray` to see the smallest possible Article response.
2. Read `ArticleSqlQuery` to see where a resource starts coordinating multiple
   query results.
3. Read `ArticleRawPdo` last to compare the same response shape without
   MediaQuery.

These resources should stay outside the ALPS profile and should not grow into a
parallel API surface.
