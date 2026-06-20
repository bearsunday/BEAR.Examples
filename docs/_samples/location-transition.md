---
layout: sample
title: "Location header as hypermedia transition"
order: 60
category: hypermedia
status: historical
source:
  - src/Resource/App/Article.php
  - tests/Hypermedia/EditorManagesArticleTest.php
test:
  - tests/Hypermedia/EditorManagesArticleTest.php
convention: docs/conventions.md#71-hypermedia-workflow-tests
upstream: [Hypermedia, HTTP Location, Resource-oriented design]
tags: [hypermedia, location, workflow-test, historical]
ai_use: "Use this when a POST creates a resource and the client must discover the new URI from Location."
ai_avoid: "Do not treat Location-following as a direct coupling smell; it is the hypermedia transition for unsafe creation."
---

## Location header as hypermedia transition

<section class="intent" markdown="1">

### Intent

Record that `Location` after `POST` is a first-class hypermedia transition in
this reference. Safe browsing usually follows HAL rels; unsafe creation returns
the newly created resource URI in `Location`.

</section>

<section class="summary" markdown="1">

### Summary

HTTP `Location` after `POST` is a first-class transition to the newly created
resource. Following it is not a direct-coupling smell.

</section>

<section class="useWhen" markdown="1">

### Use when

- A POST creates a new resource and the id is not known before the request.
- A workflow test needs to read back the created resource.
- You want the client to follow the server-provided navigation cue.

</section>

<section class="doNotUseWhen" markdown="1">

### Do not use when

- A safe transition is already advertised as a HAL rel.
- You are guessing a URI that the server did not provide.
- You are using `Location` to skip a declared rel in a read-only browsing story.

</section>

<section class="patternDiff" markdown="1">

### Pattern diff

This is a canonical rewrite shape, not a historical git diff.

```diff
- $read = $this->resource->get('app://self/article', ['id' => $guessedId]);
+ $id = $this->idFromLocation((string) $created->headers['Location']);
+ $read = $this->resource->get('app://self/article', ['id' => $id]);
```

</section>

<section class="shapeExcerpt" markdown="1">

### Shape excerpt

```php
public function onPost(ArticleCreateInput $input): static
{
    // ... create and recover id ...
    $this->code = Code::CREATED;
    $this->headers['Location'] = '/article?id=' . $created->id;

    return $this;
}

#[Depends('testCreatesAnArticle')]
public function testReadsBackTheNewArticle(ResourceObject $created): ResourceObject
{
    $id = $this->idFromLocation((string) $created->headers['Location']);

    return $this->resource->get('app://self/article', ['id' => $id]);
}
```

</section>

<section class="notes" markdown="1">

### Notes

- `Location` is the transition because the client cannot know the new id beforehand.
- The workflow still uses server-provided affordance, not a guessed URI.
- This complements, rather than replaces, HAL rel following in read-only stories.

</section>

### Links

- Source: [`src/Resource/App/Article.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/src/Resource/App/Article.php){: .goSource }
- Test: [`tests/Hypermedia/EditorManagesArticleTest.php`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/tests/Hypermedia/EditorManagesArticleTest.php){: .goTest }
- Convention: [`docs/conventions.md#71-hypermedia-workflow-tests`](https://github.com/bearsunday/MyVendor.Cms/blob/1.x/docs/conventions.md#71-hypermedia-workflow-tests){: .goConvention }
