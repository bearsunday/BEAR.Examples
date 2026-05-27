# ALPS profile

[日本語](ja/alps.md)

[var/alps/profile.json](../var/alps/profile.json) is the source of truth
for CMS semantics. It describes the whole CMS state space: public HTML
browsing, Page/Admin authentication and article-management flows, and the
HAL App resource API.

## Layers

| Layer        | Example                                                        |
|--------------|----------------------------------------------------------------|
| Ontology     | `articleTitle`, `articleStatus`, `categoryParentId`, `mediaAlt` |
| Taxonomy     | `Home`, `Article`, `ArticleList`, `AdminLogin`, `AdminArticleForm`, `Media` |
| Choreography | `goArticleList`, `goAdminLogin`, `doCompleteLogin`, `doAdminCreateArticle` |

- Safe (`go*`): GET transitions.
- Unsafe (`do*`, `unsafe`): POST, idempotent writes use `idempotent`.

## How it flows into code

```
profile.json → semantic-ex Phase 1 (Experience)   → var/fake/*.json
             → semantic-ex Phase 2 (Examples)     → var/fake/observations.md
             → semantic-ex Phase 3 (Constraints)  → var/json_schema/*.json
             → HAL resources                      → src/Resource/App/*
             → Page resources                     → src/Resource/Page/*
```

For the App API, ALPS transition names map 1:1 to HAL `_links` rels /
Resource URI conventions — e.g. `goArticle` → `app://self/article`,
`doCreateArticle` → `POST app://self/article`.

For Page/Admin, the profile records browser-visible state transitions such
as unauthenticated admin access → `goAdminLogin`, OAuth callback →
`doCompleteLogin`, and article form submissions → `doAdminCreateArticle` /
`doAdminUpdateArticle`.

## Validating the profile

```bash
asd --validate var/alps/profile.json
```

The current profile validates with 0 errors, 0 warnings, and 0 suggestions.

## Visualising

```bash
asd var/alps/profile.json               # HTML state diagram
asd var/alps/profile.json -f mermaid    # Mermaid classDiagram
```
