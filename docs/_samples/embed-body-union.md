---
layout: sample
title: "Preserve #[Embed] slots with body union"
order: 20
category: hypermedia
status: canonical
source:
  - src/Resource/App/Article.php
test:
  - tests/Hypermedia/HalEnvelopeContractTest.php
convention: docs/conventions.md
upstream: [Hypermedia, Embed, Transparent parallel execution]
tags: [embed, hal, body-shape, resource-object]
ai_use: "Use this when an onGet method has #[Embed] requests that need query parameters discovered after fetching the entity."
ai_avoid: "Do not assign $this->body = [...] after embed slots exist."
---

## Preserve `#[Embed]` slots with body union

<section class="intent" markdown="1">

### Intent

When `#[Embed]` creates request slots in `$this->body`, preserve those slots and
add scalar fields with PHP's no-overwrite array union (`+=`). This lets the HAL
renderer materialise `_embedded` while the Resource still returns flat scalar
fields.

</section>

<section class="summary" markdown="1">

### Summary

Preserve request slots created by `#[Embed]` and add scalar fields with
`$this->body += [...]` so HAL rendering can materialise embedded resources.

</section>

<section class="useWhen" markdown="1">

### Use when

- `onGet` has one or more `#[Embed]` attributes.
- Embed query parameters are only known after the main entity is fetched.
- The response must contain both embedded resources and scalar fields.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- The method has no embeds; assign a literal `$this->body = [...]` instead.
- You are handling a not-found or error body; replace the body to drop stale embed slots.
- You want to manually build `_embedded`; prefer `#[Embed]` when the dependency is declarative.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- $this->body = [
-     'id' => $article->id,
-     'title' => $article->title,
- ];
+ $this->body['author']->addQuery(['id' => $article->authorId]);
+ $this->body['category']->addQuery(['id' => $article->categoryId]);
+ $this->body += [
+     'id' => $article->id,
+     'title' => $article->title,
+ ];
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
#[Embed(rel: 'author', src: 'app://self/author')]
#[Embed(rel: 'category', src: 'app://self/category')]
#[Embed(rel: 'tagList', src: 'app://self/tags')]
public function onGet(int $id): static
{
    $article = $this->article->item($id);

    $this->body['author']->addQuery(['id' => $article->authorId]);
    $this->body['category']->addQuery(['id' => $article->categoryId]);
    $this->body['tagList']->addQuery(['articleId' => $article->id]);

    $this->body += [
        'id' => $article->id,
        'title' => $article->title,
    ];

    return $this;
}
```

</section>

<section class="notes" markdown="1">

### Notes

- `+=` keeps the left-hand embed slots and adds only missing scalar keys.
- `=` would replace the body and lose the pending embed requests.
- Error paths should intentionally assign a replacement body so failed responses do not render embeds.
- HAL rel naming stays split: Choreography verbs in `_links`, Taxonomy nouns in `_embedded`.

</section>

### Links

- Source: [`src/Resource/App/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Article.php){: .goSource }
- Test: [`tests/Hypermedia/HalEnvelopeContractTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Hypermedia/HalEnvelopeContractTest.php){: .goTest }
- Convention: [`docs/conventions.md`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/conventions.md){: .goConvention }
