# BEAR.Sunday Manual Map

This document maps the BEAR.Sunday 1.0 manual to this reference CMS. It is
written for humans and AI agents that start from a manual chapter and need to
find the executable example, or confirm that the topic is intentionally outside
this repository's scope.

GitHub issue [#33](https://github.com/bearsunday/MyVendor.Cms/issues/33)
tracks the live roadmap. `docs/scope.md` remains the source of truth for
implemented, deferred, and by-design items.

## Status Legend

| Status | Meaning |
|---|---|
| Done | Demonstrated in code today |
| Partial | Exists, but only in a limited form |
| Missing | Manual covers it, but this repository has no demonstration yet |
| By design | Explicitly out of scope for this reference CMS |
| N/A | Orientation or reference material with no direct code mapping |

## Orientation

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [index.md](https://bearsunday.github.io/manuals/1.0/en/) | Framework overview | N/A | Read with [README.md](../README.md) |
| [1page.md](https://bearsunday.github.io/manuals/1.0/en/1page.html) | Complete manual in one page | N/A | Aggregates the manual; use this map for repository anchors |
| [quick-start.md](https://bearsunday.github.io/manuals/1.0/en/quick-start.html) | First application setup | Partial | [README.md](../README.md#quick-start), [README.md](../README.md#setup) |
| [tutorial.md](https://bearsunday.github.io/manuals/1.0/en/tutorial.html) | Resource application tutorial | Done | [src/Resource/App/Article.php](../src/Resource/App/Article.php), [tests/Resource/App/ArticleTest.php](../tests/Resource/App/ArticleTest.php) |
| [tutorial2.md](https://bearsunday.github.io/manuals/1.0/en/tutorial2.html) | Hypermedia, forms, DI | Partial | Hypermedia is done; auth/CSRF form boundary is deferred in [scope.md](scope.md#deferred--not-built) |
| [tutorial3.md](https://bearsunday.github.io/manuals/1.0/en/tutorial3.html) | CLI application tutorial | Partial | [bin/cli/article-show](../bin/cli/article-show), [bin/cli/article-list](../bin/cli/article-list) |
| [setup.md](https://bearsunday.github.io/manuals/1.0/en/setup.html) | Environment setup | Done | [README.md](../README.md#requirements), [README.md](../README.md#setup) |
| [setup-reference.md](https://bearsunday.github.io/manuals/1.0/en/setup-reference.html) | Detailed setup reference | Partial | Local project setup only; no full framework setup mirror |
| [examples.md](https://bearsunday.github.io/manuals/1.0/en/examples.html) | Example applications | N/A | This repository is itself the reference example |
| [reference.md](https://bearsunday.github.io/manuals/1.0/en/reference.html) | API reference index | N/A | See generated [docs/index.html](index.html) and [docs/openapi.json](openapi.json) |
| [version.md](https://bearsunday.github.io/manuals/1.0/en/version.html) | Versioning policy | N/A | Framework policy, not repository behavior |

## Core Framework

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [application.md](https://bearsunday.github.io/manuals/1.0/en/application.html) | Application bootstrap and lifecycle | Done | [autoload.php](../autoload.php), [src/Module/App.php](../src/Module/App.php) |
| [module.md](https://bearsunday.github.io/manuals/1.0/en/module.html) | Module composition | Done | [src/Module/AppModule.php](../src/Module/AppModule.php), [src/Module/FakeModule.php](../src/Module/FakeModule.php), [src/Module/TestModule.php](../src/Module/TestModule.php), [src/Module/HtmlModule.php](../src/Module/HtmlModule.php) |
| [package.md](https://bearsunday.github.io/manuals/1.0/en/package.html) | Package layout | Done | [composer.json](../composer.json), [docs/architecture.md](architecture.md) |
| [tech.md](https://bearsunday.github.io/manuals/1.0/en/tech.html) | Design philosophy | N/A | Philosophy document; read alongside [docs/architecture.md](architecture.md) |

## DI, AOP, and Attributes

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [di.md](https://bearsunday.github.io/manuals/1.0/en/di.html) | Bindings, providers, scopes | Done | [src/Module/AppModule.php](../src/Module/AppModule.php) |
| [aop.md](https://bearsunday.github.io/manuals/1.0/en/aop.html) | Method interception | Done | `DbQueryInterceptor` via `MediaQuerySqlModule` on every `#[DbQuery]` call |
| [attribute.md](https://bearsunday.github.io/manuals/1.0/en/attribute.html) | Attributes for DI and AOP | Done | `#[Embed]`, `#[Link]`, `#[JsonSchema]`, `#[Input]`, and `#[DbQuery]` across [src/Resource](../src/Resource) and [src/Query](../src/Query) |
| [upgrade/injector.md](https://bearsunday.github.io/manuals/1.0/en/upgrade/injector.html) | Injector upgrade notes | N/A | Framework upgrade note |

## Resource System

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [resource.md](https://bearsunday.github.io/manuals/1.0/en/resource.html) | Resource class | Done | [src/Resource/App/Article.php](../src/Resource/App/Article.php) |
| [resource_param.md](https://bearsunday.github.io/manuals/1.0/en/resource_param.html) | Method parameter injection | Done | [src/Input/ArticleCreateInput.php](../src/Input/ArticleCreateInput.php), [src/Input/ArticleUpdateInput.php](../src/Input/ArticleUpdateInput.php) |
| [resource_link.md](https://bearsunday.github.io/manuals/1.0/en/resource_link.html) | `#[Link]` and `#[Embed]` | Done | [src/Resource/App/Article.php](../src/Resource/App/Article.php), [tests/Hypermedia](../tests/Hypermedia) |
| [resource_renderer.md](https://bearsunday.github.io/manuals/1.0/en/resource_renderer.html) | Body rendering | Done | HAL through the API context; Qiq through [src/Module/HtmlModule.php](../src/Module/HtmlModule.php) and [src/Resource/Page](../src/Resource/Page) |
| [resource_bp.md](https://bearsunday.github.io/manuals/1.0/en/resource_bp.html) | Resource best practices | Done | [docs/conventions.md](conventions.md) |
| [router.md](https://bearsunday.github.io/manuals/1.0/en/router.html) | URI to resource mapping | Done | BEAR.Package `WebRouterModule` default plus the App/Page context split |

## Hypermedia and API Design

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [hypermedia-api.md](https://bearsunday.github.io/manuals/1.0/en/hypermedia-api.html) | HAL and ALPS | Done | [var/alps/profile.json](../var/alps/profile.json), [docs/alps.md](alps.md), [tests/Hypermedia/HalEnvelopeContractTest.php](../tests/Hypermedia/HalEnvelopeContractTest.php) |
| [content-negotiation.md](https://bearsunday.github.io/manuals/1.0/en/content-negotiation.html) | Multiple media surfaces | Done | `hal-api-app` for HAL JSON, `html-hal-app` for Qiq/Page HTML |
| [apidoc.md](https://bearsunday.github.io/manuals/1.0/en/apidoc.html) | Generated API documentation | Done | [docs/index.html](index.html), [docs/openapi.json](openapi.json), `composer doc` |
| [psr7.md](https://bearsunday.github.io/manuals/1.0/en/psr7.html) | PSR-7 `ServerRequestInterface` injection | Missing | Candidate small demo: cookies, headers, or client IP injection |

## HTML and Templating

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [html.md](https://bearsunday.github.io/manuals/1.0/en/html.html) | HTML rendering overview | Done | [src/Resource/Page](../src/Resource/Page), [templates/Page](../templates/Page) |
| [html-qiq.md](https://bearsunday.github.io/manuals/1.0/en/html-qiq.html) | Qiq template engine | Done | [src/Module/HtmlModule.php](../src/Module/HtmlModule.php), [templates/Page](../templates/Page) |
| [html-twig-v1.md](https://bearsunday.github.io/manuals/1.0/en/html-twig-v1.html) | Twig v1 | By design | Qiq is the chosen renderer; see [scope.md](scope.md#by-design-intentional-omissions) |
| [html-twig-v2.md](https://bearsunday.github.io/manuals/1.0/en/html-twig-v2.html) | Twig v2 | By design | Same as above |
| [js-ui.md](https://bearsunday.github.io/manuals/1.0/en/js-ui.html) | JavaScript SSR | By design | JS-enhanced admin is out of scope; see [scope.md](scope.md#by-design-intentional-omissions) |
| [form.md](https://bearsunday.github.io/manuals/1.0/en/form.html) | Form validation and CSRF | Partial | Form POST/PRG exists in [src/Resource/Page/Admin/Article.php](../src/Resource/Page/Admin/Article.php), but `Ray\WebFormModule` / `#[FormValidation]` and CSRF are deferred |

## Database

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [database.md](https://bearsunday.github.io/manuals/1.0/en/database.html) | Database integration overview | Done | `AuraSqlModule` and `MediaQuerySqlModule` in [src/Module/AppModule.php](../src/Module/AppModule.php) |
| [database.media.md](https://bearsunday.github.io/manuals/1.0/en/database.media.html) | Ray.MediaQuery `#[DbQuery]` | Done | [src/Query/ArticleQueryInterface.php](../src/Query/ArticleQueryInterface.php), [src/Query/ArticleCommandInterface.php](../src/Query/ArticleCommandInterface.php), [var/db/sql](../var/db/sql) |
| [database.aura.md](https://bearsunday.github.io/manuals/1.0/en/database.aura.html) | Aura.Sql raw PDO | Partial | [src/Resource/App/Variations/ArticleRawPdo.php](../src/Resource/App/Variations/ArticleRawPdo.php) only; main path uses MediaQuery |
| [database.cake.md](https://bearsunday.github.io/manuals/1.0/en/database.cake.html) | CakeDB | By design | MediaQuery is the chosen DB layer |
| [database.dbal.md](https://bearsunday.github.io/manuals/1.0/en/database.dbal.html) | Doctrine DBAL | By design | MediaQuery is the chosen DB layer |
| [databasev1.md](https://bearsunday.github.io/manuals/1.0/en/databasev1.html) | Legacy database integration | By design | Obsolete for this reference |

## Validation and Security

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [validation.md](https://bearsunday.github.io/manuals/1.0/en/validation.html) | JSON Schema validation | Done | `#[JsonSchema]` on resources, [var/json_validate](../var/json_validate), [var/json_schema](../var/json_schema) |
| [security.md](https://bearsunday.github.io/manuals/1.0/en/security.html) | Authentication and authorization | Partial | OAuth flow exists in [src/Resource/App/Auth.php](../src/Resource/App/Auth.php); admin auth/authz is designed in [docs/journal/auth-boundary-plan.md](journal/auth-boundary-plan.md) and deferred as D2 |

## Caching

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [cache.md](https://bearsunday.github.io/manuals/1.0/en/cache.html) | QueryRepository cache, `#[Cacheable]`, dependency tags | Done | [src/Resource/App/Cache/Author.php](../src/Resource/App/Cache/Author.php), [src/Resource/App/Cache/AuthorProfile.php](../src/Resource/App/Cache/AuthorProfile.php), [src/Resource/App/Cache/ArticleTags.php](../src/Resource/App/Cache/ArticleTags.php), `composer demo:cache` |
| [redis-dns.md](https://bearsunday.github.io/manuals/1.0/en/redis-dns.html) | Redis cache adapter | Missing | Cache showcase uses in-memory `ArrayAdapter`; Redis binding remains a future demo |

## Performance and Production

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [production.md](https://bearsunday.github.io/manuals/1.0/en/production.html) | Production tuning and `ProdModule` | Missing | No `src/Module/ProdModule.php`; no opcache/preload guide |
| [server.md](https://bearsunday.github.io/manuals/1.0/en/server.html) | Swoole and RoadRunner | By design | Runtime servers are out of scope for this reference |
| [async.md](https://bearsunday.github.io/manuals/1.0/en/async.html) | Parallel `#[Embed]` execution | Done | [bin/async.php](../bin/async.php), `composer async`, `composer parallel:up` |
| [stream.md](https://bearsunday.github.io/manuals/1.0/en/stream.html) | Streaming responses | Done | [src/Resource/App/Variations/MediaStream.php](../src/Resource/App/Variations/MediaStream.php) |

## Testing

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [test.md](https://bearsunday.github.io/manuals/1.0/en/test.html) | Resource testing and DI contexts | Done | [tests/AbstractAppTestCase.php](../tests/AbstractAppTestCase.php), [tests/Fake/FakeSqlQuery.php](../tests/Fake/FakeSqlQuery.php), [tests/Hypermedia](../tests/Hypermedia), [tests/Smoke](../tests/Smoke), [tests/Integration](../tests/Integration) |

## Tooling and Developer Experience

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [cli.md](https://bearsunday.github.io/manuals/1.0/en/cli.html) | CLI runner via `#[Cli]` | Partial | [bin/cli/article-show](../bin/cli/article-show), [bin/cli/article-list](../bin/cli/article-list); write-side CLI is deferred as D4 |
| [coding-guide.md](https://bearsunday.github.io/manuals/1.0/en/coding-guide.html) | Coding standards | Done | [docs/conventions.md](conventions.md), `composer cs`, `composer sa` |
| [types.md](https://bearsunday.github.io/manuals/1.0/en/types.html) | Strict resource and DI types | Done | `final readonly` entities in [src/Entity](../src/Entity), typed inputs in [src/Input](../src/Input) |
| [types-utility.md](https://bearsunday.github.io/manuals/1.0/en/types-utility.html) | PHPDoc utility types | Done | `array{...}` shapes in [src/Resource/App/Variations](../src/Resource/App/Variations) and query interfaces |
| [ai-assistant.md](https://bearsunday.github.io/manuals/1.0/en/ai-assistant.html) | LLM-oriented implementation support | Done | [docs/llms.txt](llms.txt), [var/alps/profile.json](../var/alps/profile.json), this manual map |

## Architecture Composition

| Manual | Concept | Status | Code or doc anchor |
|---|---|---|---|
| [import.md](https://bearsunday.github.io/manuals/1.0/en/import.html) | Importing sub-applications | By design | Out of this reference's scope |
| [aaas.md](https://bearsunday.github.io/manuals/1.0/en/aaas.html) | Application as a Service | By design | Out of this reference's scope |

## Reference Patterns Beyond The Manual

These patterns are important for AI implementation even when they are not a
single manual chapter.

| Pattern | Anchor |
|---|---|
| `FetchInjectionFactory`: DI into a hydrated entity | [src/Module/AppModule.php](../src/Module/AppModule.php), [src/Entity/Article.php](../src/Entity/Article.php) |
| Input DTO via `#[Input]` contrasted with scalar resource params | [src/Input/ArticleCreateInput.php](../src/Input/ArticleCreateInput.php), [src/Resource/App/Author.php](../src/Resource/App/Author.php) |
| Tri-state optional collection input (`null`, empty list, list) | [src/Input/ArticleCreateInput.php](../src/Input/ArticleCreateInput.php), [src/Resource/App/Article.php](../src/Resource/App/Article.php) |
| MediaQuery pager / `PagesInterface` | [src/Query/ArticleQueryInterface.php](../src/Query/ArticleQueryInterface.php) |
| MediaQuery SELECT result class | [src/Query/ArticleSelectionQueryInterface.php](../src/Query/ArticleSelectionQueryInterface.php) |
| MediaQuery DML metadata return | [src/Query/Samples/ArticleAffectedRowsCommandInterface.php](../src/Query/Samples/ArticleAffectedRowsCommandInterface.php) |
| Natural-key post-insert lookup | [src/Resource/App/Article.php](../src/Resource/App/Article.php), [src/Query/ArticleQueryInterface.php](../src/Query/ArticleQueryInterface.php) |
| Manual `_embedded` build for ID-after-fetch cases | [src/Resource/App/Article.php](../src/Resource/App/Article.php) |
| Three Article GET implementation variations | [src/Resource/App/Variations](../src/Resource/App/Variations), `composer demo:variations` |
| PRG redirect on admin writes | [src/Resource/Page/Admin/Article.php](../src/Resource/Page/Admin/Article.php), [src/Resource/Page/Admin/ArticleDelete.php](../src/Resource/Page/Admin/ArticleDelete.php) |
| Two-stage Fake-vs-Test module pattern | [src/Module/FakeModule.php](../src/Module/FakeModule.php), [src/Module/TestModule.php](../src/Module/TestModule.php) |
| HAL envelope contract test | [tests/Hypermedia/HalEnvelopeContractTest.php](../tests/Hypermedia/HalEnvelopeContractTest.php) |
| `#[Depends]`-chained hypermedia workflow tests | [tests/Hypermedia](../tests/Hypermedia) |
| QueryRepository cache dependency patterns | [src/Resource/App/Cache](../src/Resource/App/Cache), [docs/conventions.md](conventions.md#cache--cacheable-and-cross-resource-invalidation) |
| BEAR.Async module-swap entrypoint | [bin/async.php](../bin/async.php) |
| BEAR.Streamer transfer-mode variation | [src/Resource/App/Variations/MediaStream.php](../src/Resource/App/Variations/MediaStream.php) |

## Roadmap Summary

The detailed roadmap lives in [scope.md](scope.md#deferred--not-built) and
issue [#33](https://github.com/bearsunday/MyVendor.Cms/issues/33). The next
highest-value gaps are:

1. Auth boundary for `Page/Admin/*`: typed `UserInterface` /
   `AdminUserInterface`, per-record authorization, and CSRF.
2. Write-side `#[Cli]` commands for create/update/delete flows.
3. PSR-7 `ServerRequestInterface` injection example.
4. Production `ProdModule` and deployment tuning notes.
5. Redis cache adapter binding for the existing cache showcase.
6. Real-CMS patterns not deeply covered by the manual: file upload, resource
   crawl, N+1 resolution, transaction wrapping, exception-to-HTTP mapping,
   search, and structured logging.
