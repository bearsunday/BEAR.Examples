---
layout: sample
title: "Hypermedia workflow test"
order: 40
category: testing
status: canonical
source:
  - tests/Hypermedia/EditorManagesArticleTest.php
  - tests/Hypermedia/ReaderBrowsesByTagTest.php
  - tests/Hypermedia/AbstractWorkflowTestCase.php
test:
  - tests/Hypermedia/EditorManagesArticleTest.php
  - tests/Hypermedia/ReaderBrowsesByTagTest.php
convention: docs/conventions.md#71-hypermedia-workflow-tests
upstream: [Hypermedia, HAL links, Ease of Testing, HTTP Location]
tags: [hypermedia, hal, alps, workflow-test]
ai_use: "Use this when validating that resources are connected by HAL rels as an ALPS user story."
ai_avoid: "Do not compress a story into one method with hard-coded mid-chain URIs."
---

## Hypermedia workflow test

<section class="intent" markdown="1">

### Intent

Write one user story per test class and connect each step with `#[Depends]`.
Only the entry point is hard-coded; later steps use returned `ResourceObject`s
and hypermedia transitions so ALPS/HAL connectivity is tested, not just endpoint
shape. In this project both HAL rels and HTTP `Location` after `POST` are valid
hypermedia transitions.

</section>

<section class="summary" markdown="1">

### Summary

Use `#[Depends]`-linked story steps to test traversal through HAL rels or
HTTP `Location`, instead of compressing the journey into one endpoint assertion.

</section>

<section class="useWhen" markdown="1">

### Use when

- The behavior is a user journey across multiple resources.
- You need to prove HAL rels or `Location` headers connect the graph correctly.
- Testdox output should read like a story.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- You only need field-level response shape checks; use Resource tests and schemas.
- You need to assert every property in a response body.
- You are tempted to hard-code intermediate URIs instead of using rels or `Location`.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- public function testEditorLifecycle(): void
- {
-     $created = $this->resource->post('app://self/article', $values);
-     $read = $this->resource->get('app://self/article', ['id' => $id]);
-     $this->resource->put('app://self/article', $updated);
-     $this->resource->delete('app://self/article', ['id' => $id]);
- }
+ public function testCreatesAnArticle(): ResourceObject { ... }
+
+ #[Depends('testCreatesAnArticle')]
+ public function testReadsBackTheNewArticle(ResourceObject $created): ResourceObject { ... }
+
+ #[Depends('testReadsBackTheNewArticle')]
+ public function testRevisesTheArticle(ResourceObject $read): ResourceObject { ... }
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
abstract class AbstractWorkflowTestCase extends AbstractAppTestCase
{
    protected function follow(ResourceObject $ro, string $rel, array $vars = []): ResourceObject
    {
        return $this->resource->href($rel, $vars, $ro);
    }
}

final class ReaderBrowsesByTagTest extends AbstractWorkflowTestCase
{
    public function testOpensTagList(): ResourceObject
    {
        return $this->resource->get('app://self/tags'); // entry URI
    }

    #[Depends('testOpensTagList')]
    public function testPicksATag(ResourceObject $tags): ResourceObject
    {
        return $this->follow($tags, 'goTag', ['id' => $tags->body['items'][0]['id']]);
    }
}

final class EditorManagesArticleTest extends AbstractWorkflowTestCase
{
    #[Depends('testCreatesAnArticle')]
    public function testReadsBackTheNewArticle(ResourceObject $created): ResourceObject
    {
        $id = $this->idFromLocation((string) $created->headers['Location']);

        return $this->resource->get('app://self/article', ['id' => $id]);
    }
}
```

</section>

<section class="notes" markdown="1">

### Notes

- Class name is the story title; method names are the steps.
- The first step may use an entry URI.
- Safe navigation uses HAL rels through `follow()` / `ResourceInterface::href()`.
- Unsafe creation can return `Location`; following that header is also hypermedia, not a direct coupling smell.
- Per-step field validation belongs to `#[JsonSchema]`, not to workflow tests.
- Contract pins such as HAL envelope shape belong in separate contract tests.

</section>

### Links

- Source: [`tests/Hypermedia/EditorManagesArticleTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Hypermedia/EditorManagesArticleTest.php){: .goSource }
- Rel example: [`tests/Hypermedia/ReaderBrowsesByTagTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Hypermedia/ReaderBrowsesByTagTest.php){: .goSource }
- Helper: [`tests/Hypermedia/AbstractWorkflowTestCase.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Hypermedia/AbstractWorkflowTestCase.php){: .goSource }
- Convention: [`docs/conventions.md#71-hypermedia-workflow-tests`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/conventions.md#71-hypermedia-workflow-tests){: .goConvention }
