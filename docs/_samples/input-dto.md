---
layout: sample
title: "Input DTO via #[Input]"
order: 10
category: resource
status: canonical
source:
  - src/Resource/App/Article.php
  - src/Input/ArticleCreateInput.php
test:
  - tests/Resource/App/ArticleTest.php
convention: docs/conventions.md
upstream: [Resource parameters, JsonSchema validation, Application as Documentation]
tags: [input-dto, validation, json-schema, resource-boundary]
ai_use: "Use this when a Resource method needs a coherent typed request shape."
ai_avoid: "Do not convert scalar contrast examples just for symmetry."
---

## Input DTO via `#[Input]`

<section class="intent" markdown="1">

### Intent

Use a typed Input DTO at the Resource boundary when request fields form one
coherent shape. The DTO names the request, normalises its fields, and pairs with
`#[JsonSchema(params: ...)]` for request validation.

</section>

<section class="summary" markdown="1">

### Summary

Group coherent request shapes into typed DTOs at the Resource boundary. Do
not mechanically convert scalar contrast examples just for symmetry.

</section>

<section class="useWhen" markdown="1">

### Use when

- The input has many fields or one named request shape.
- The request needs shape semantics such as `null`, `[]`, and `list<int>`.
- The Resource still orchestrates work before calling Command interfaces.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- The scalar form is intentionally kept as a contrast sample.
- The Resource-to-Command boundary is a better teaching surface as scalar args.
- You are only trying to make every Resource symmetric.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- public function onPost(string $slug, string $title, string $body, ?array $tagIds = null): static
+ public function onPost(#[Input] ArticleCreateInput $input): static
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]
public function onPost(#[Input] ArticleCreateInput $input): static
{
    $this->articleCmd->add(
        $input->slug,
        $input->title,
        $input->body,
        $input->excerpt,
        $input->status,
        $this->sqlDateTime->fromRfc3339($input->publishedAt),
        $input->authorId,
        $input->categoryId,
    );

    $created = $this->article->bySlug($input->slug);
    assert($created !== null);

    return $this;
}
```

</section>

<section class="notes" markdown="1">

### Notes

- Pair `#[Input]` with `#[JsonSchema(params: ...)]` at the Resource method.
- Keep Command interfaces scalar in this project; unpack the DTO at the Resource boundary.
- `ArticleCreateInput` demonstrates native array input; malformed non-array shapes fail before the constructor runs.
- Page form receivers stay scalar for now so generated docs remain clear.

</section>

### Links

- Source: [`src/Resource/App/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Article.php){: .goSource }
- DTO: [`src/Input/ArticleCreateInput.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Input/ArticleCreateInput.php){: .goSource }
- Test: [`tests/Resource/App/ArticleTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Resource/App/ArticleTest.php){: .goTest }
- Convention: [`docs/conventions.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/conventions.md){: .goConvention }
