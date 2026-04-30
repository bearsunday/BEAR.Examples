# Code Reading Guide

This guide is for reading the repository as code, not for learning every API
surface. Start from one canonical flow, then branch out.

## First Pass

Read Article GET first. It touches the main architectural pieces without the
extra write-path concerns.

1. `src/Resource/App/Article.php` — Resource boundary, HAL links/embeds, and
   response body shape.
2. `src/Entity/Article.php` — domain data and presentation helpers that should
   not be scattered across arrays.
3. `src/Query/ArticleQueryInterface.php` — declarative read contract via
   `#[DbQuery]`.
4. `var/db/sql/article_item.sql` — SQL shape expected by the query interface.
5. `tests/Resource/App/ArticleTest.php` — behavior contract from the client
   side.
6. `tests/Fake/FakeSqlQuery.php` — fake dispatch rules; this explains the
   query contract more concretely than the interface alone.

## What To Notice

| Area | Files | Reading focus |
|---|---|---|
| Resource layer | `src/Resource/App/*` | HTTP method shape, status codes, body construction, `#[JsonSchema]`, `#[Link]`, and `#[Embed]`. |
| Entity layer | `src/Entity/*` | Immutable domain data, computed fields, and behavior that should stay near the data. |
| Query layer | `src/Query/*` | Read/write split, method names, and how attributes map PHP methods to SQL files. |
| SQL layer | `var/db/sql/*` | Column aliases, parameter names, and row shapes consumed by entities/resources. |
| Composition | `src/Module/*` | Context-specific wiring: production, fake, and test. |
| Tests and fakes | `tests/*` | The executable contract. Fakes should preserve semantics, not merely return convenient data. |

## Second Pass

After the canonical Article flow, compare these variations:

- `src/Resource/App/Articles.php` for collection filtering and pagination shape.
- `src/Resource/App/Article.php::onPost()` and `onPut()` for input DTOs,
  write commands, and re-select-after-write.
- `src/Resource/App/Variations/README.md` for the Article GET comparison set.
- `docs/conventions.md` when you need the rule behind a naming or shape choice.

## Reading Rule

Treat `docs/conventions.md` as the current rulebook. Treat
`docs/journal/*` as historical context: useful for understanding why a decision
exists, but not the source of truth for new code.
