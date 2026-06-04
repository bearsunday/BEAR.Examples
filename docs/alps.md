# ALPS profile

[日本語](ja/alps.md)

[var/alps/profile.json](../var/alps/profile.json) is the source of truth
for CMS semantics.

## Layers

| Layer        | Example                                                        |
|--------------|----------------------------------------------------------------|
| Ontology     | `articleTitle`, `articleStatus`, `categoryParentId`, `mediaAlt`, `file` |
| Taxonomy     | `Article`, `ArticleList`, `Category`, `Tag`, `Author`, `Media` |
| Choreography | `goArticleList`, `goArticle`, `doCreateArticle`, `doUploadMediaFile` |

- Safe (`go*`): GET transitions.
- Unsafe (`do*`, `unsafe`): POST, idempotent writes use `idempotent`.

## How it flows into code

```
profile.json → semantic-ex Phase 1 (Experience)   → var/fake/*.json
             → semantic-ex Phase 2 (Examples)     → var/fake/observations.md
             → semantic-ex Phase 3 (Constraints)  → var/json_schema/*.json
             → HAL resources                      → src/Resource/App/*
```

ALPS transition names map 1:1 to HAL `_links` rels / Resource URI
conventions — e.g. `goArticle` → `app://self/article`, `doCreateArticle`
→ `POST app://self/article`.

Media has two write transitions by design: `doCreateMedia` creates metadata
from an existing URL, while `doUploadMediaFile` demonstrates `#[InputFile]`
binary upload through `app://self/media-upload`.

## Validating the profile

Run `composer setup` once to install the npm ASD dependency.

```bash
npm run alps:validate
```

The current profile validates with 0 errors and 0 warnings.

## Visualising

```bash
npm run doc:alps                        # HTML and static SVG diagrams
npx --no-install asd -f mermaid var/alps/profile.json
```
