# ALPS profile

[var/alps/profile.json](../var/alps/profile.json) is the source of truth
for CMS semantics.

## Layers

| Layer        | Example                                                        |
|--------------|----------------------------------------------------------------|
| Ontology     | `articleTitle`, `articleStatus`, `categoryParentId`, `mediaAlt` |
| Taxonomy     | `Article`, `ArticleList`, `Category`, `Tag`, `Author`, `Media` |
| Choreography | `goArticleList`, `goArticle`, `doCreateArticle`, `doDeleteTag` |

- Safe (`go*`): GET transitions.
- Unsafe (`do*`, `unsafe`): POST, idempotent writes use `idempotent`.

## How it flows into code

```
profile.json → semantic-ex Phase 1 (Experience)   → var/fake/*.json
             → semantic-ex Phase 2 (Examples)     → var/fake/observations.md
             → semantic-ex Phase 3 (Constraints)  → var/schema/*.json
             → HAL resources                      → src/Resource/App/*
```

ALPS transition names map 1:1 to HAL `_links` rels / Resource URI
conventions — e.g. `goArticle` → `app://self/article`, `doCreateArticle`
→ `POST app://self/article`.

## Validating the profile

```bash
asd --validate var/alps/profile.json
```

The current profile validates with 0 errors and 0 warnings.

## Visualising

```bash
asd var/alps/profile.json               # HTML state diagram
asd var/alps/profile.json -f mermaid    # Mermaid classDiagram
```
