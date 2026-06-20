---
layout: sample
title: "FakeSqlQuery as real in-memory infrastructure"
order: 30
category: testing
status: canonical
source:
  - tests/Fake/FakeSqlQuery.php
  - src/Module/FakeModule.php
test:
  - tests/Smoke/MediaQuerySmokeTest.php
convention: docs/conventions.md
upstream: [Context-based DI bindings, Ease of Testing, SQL execution via interfaces]
tags: [fake, media-query, dbquery, no-db-tests]
ai_use: "Use this when the whole Resource stack should run without a database while preserving #[DbQuery] dispatch semantics."
ai_avoid: "Do not replace Resource tests with mocks that bypass DI or MediaQuery dispatch."
---

## FakeSqlQuery as real in-memory infrastructure

<section class="intent" markdown="1">

### Intent

Bind `SqlQueryInterface` to an in-memory implementation so the same Resource,
Query interface, `#[DbQuery]`, validation, and hypermedia stack runs without a
real database. The fake is infrastructure, not a per-test mock.

</section>

<section class="summary" markdown="1">

### Summary

Replace only `SqlQueryInterface` with deterministic in-memory infrastructure
so Resource, DI, `#[DbQuery]`, validation, and hypermedia are still exercised
without a database.

</section>

<section class="useWhen" markdown="1">

### Use when

- Unit and workflow tests should be hermetic and database-free.
- You still want to exercise DI, Resource methods, Query interfaces, and `#[DbQuery]` names.
- Fake data is deterministic and shared with schema/docs generation.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- You need to verify actual SQL syntax or database behavior; use smoke/integration tests.
- A mock would bypass the Resource graph you are trying to validate.
- The fake's dispatch table would hide a missing SQL id instead of failing loudly.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- $article = $this->createMock(ArticleQueryInterface::class);
- $resource = new Article($article, ...);
+ $this->bind(SqlQueryInterface::class)
+     ->to(FakeSqlQuery::class)
+     ->in(Scope::SINGLETON);
+ $resource = Injector::getInstance('test-hal-api-app')
+     ->getInstance(ResourceInterface::class);
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
final class FakeModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(SqlQueryInterface::class)
            ->to(FakeSqlQuery::class)
            ->in(Scope::SINGLETON);
    }
}

final class FakeSqlQuery implements SqlQueryInterface
{
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null
    {
        if (in_array($sqlId, self::WRITE_SQL_IDS, true)) {
            $this->mutate($sqlId, $values);

            return null;
        }

        return match ($sqlId) {
            'article_item' => $this->findArticleById((int) $values['id']),
            default => throw new LogicException("unknown row sqlId '{$sqlId}'"),
        };
    }
}
```

</section>

<section class="notes" markdown="1">

### Notes

- `DbQueryInterceptor` routes writes through `getRow` / `getRowList`; the fake must mirror that contract.
- Unknown SQL ids should throw immediately so missing fake coverage is visible.
- Real SQL still needs smoke tests; the fake proves Resource behavior, not database syntax.
- The fake reads `var/fake/*.json`, so generated data, tests, and docs share one source.

</section>

### Links

- Source: [`tests/Fake/FakeSqlQuery.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Fake/FakeSqlQuery.php){: .goSource }
- Module: [`src/Module/FakeModule.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Module/FakeModule.php){: .goSource }
- Test: [`tests/Smoke/MediaQuerySmokeTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Smoke/MediaQuerySmokeTest.php){: .goTest }
- Convention: [`docs/conventions.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/conventions.md){: .goConvention }
