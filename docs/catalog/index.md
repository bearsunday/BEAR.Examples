---
layout: default
title: Executable Pattern Catalog
permalink: /catalog/
---

<div class="patternCatalog" markdown="1">

# Executable Pattern Catalog

This is a catalog of canonical BEAR.Sunday implementation shapes in MyVendor.Cms. It is for humans and AI agents that want to copy working patterns from source and tests; pattern diffs are canonical rewrite shapes, not historical git diffs.

{% assign samples = site.samples | sort: "order" %}
{% for sample in samples %}
- <span class="sampleCard"><a class="goCatalogSample" href="{{ sample.url | relative_url }}">{{ sample.title }}</a> — <span class="status">{{ sample.status }}</span>{% if sample.ai_use %} <span class="aiUse">{{ sample.ai_use }}</span>{% endif %}</span>
{% endfor %}
- <a class="goGeneratedContract" href="{{ '/index.html' | relative_url }}">API docs</a> / <a class="goGeneratedContract" href="{{ '/alps.html' | relative_url }}">ALPS</a> / <a class="goGeneratedContract" href="{{ '/openapi.json' | relative_url }}">OpenAPI</a> / <a class="goGeneratedContract" href="{{ '/llms.txt' | relative_url }}">llms.txt</a>
- [How to read this catalog]({{ '/catalog/how-to-read/' | relative_url }})
- [BEAR.Sunday llms-full.txt](https://bearsunday.github.io/llms-full.txt)

</div>
