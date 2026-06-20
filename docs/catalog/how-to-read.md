---
title: How to Read This Catalog
permalink: /catalog/how-to-read/
---

<article class="catalogPage" markdown="1">

# How to Read This Catalog

<section class="readerJob" markdown="1">

## Reader job

This page explains how a human or AI agent should read the MyVendor.Cms catalog.
The job is not to memorize every file. The job is to choose a pattern, copy only
when the same conditions apply, and follow the linked source and tests before
editing code.

</section>

<section class="summary" markdown="1">

## Summary

This page explains how to use the catalog: choose a pattern, copy only when
conditions match, and follow Source / Test / Convention before editing code.

</section>

<section class="meaningDeclared" markdown="1">

## Meaning is declared

The catalog is Markdown-authored, but important elements are marked with classes
that match the ALPS profile at [`catalog.alps.json`]({{ '/catalog/catalog.alps.json' | relative_url }}).
For example, a sample page contains classes such as `sampleCard`, `aiGuidance`,
`patternDiff`, `shapeExcerpt`, `goSource`, and `goTest`.

These classes are semantic bindings, not decorative CSS hooks. A semantic reader
can pair the raw HTML with the declared profile instead of guessing meaning from
headings, colors, or page position.

</section>

<section class="workflowStep" markdown="1">

## Workflow

1. Start from the [catalog index]({{ '/catalog/' | relative_url }}) and choose a sample by status and intent.
2. Read `aiUse` and `aiAvoid` first. They are the guardrails against over-copying.
3. Treat `patternDiff` as a canonical rewrite shape, not as repository history.
4. Treat `shapeExcerpt` as a shape excerpt. It is not guaranteed to be complete copy-paste code.
5. Follow `goSource`, `goTest`, and `goConvention` before changing application code.

</section>

<section class="catalogPage" markdown="1">

## Try one sample

- [BDR slice: Bound / Domain / Resource]({{ '/samples/bdr-slice/' | relative_url }}){: .goCatalogSample } — start here before narrower patterns.
- [Input DTO via `#[Input]`]({{ '/samples/input-dto/' | relative_url }}){: .goCatalogSample } — Resource boundary input shape.
- [Preserve `#[Embed]` slots with body union]({{ '/samples/embed-body-union/' | relative_url }}){: .goCatalogSample } — HAL embed safety.
- [Location header as hypermedia transition]({{ '/samples/location-transition/' | relative_url }}){: .goCatalogSample } — unsafe creation navigation.

</section>

<section class="notes" markdown="1">

## Notes

- `canonical` means copy the pattern when the same conditions apply.
- `showcase` means copy the shape, but keep the demonstration boundary unless the card says otherwise.
- `by-design` means the asymmetry is intentional, not missing work.
- The catalog complements the official [BEAR.Sunday llms-full.txt](https://bearsunday.github.io/llms-full.txt); it does not replace the manual.

</section>

</article>
