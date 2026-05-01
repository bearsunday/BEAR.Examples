# ALPS profile

[English](../alps.md)

[var/alps/profile.json](../../var/alps/profile.json) は CMS のセマンティクス
における source of truth です。

## レイヤー

| Layer        | Example                                                        |
|--------------|----------------------------------------------------------------|
| Ontology     | `articleTitle`、`articleStatus`、`categoryParentId`、`mediaAlt` |
| Taxonomy     | `Article`、`ArticleList`、`Category`、`Tag`、`Author`、`Media` |
| Choreography | `goArticleList`、`goArticle`、`doCreateArticle`、`doDeleteTag` |

- Safe (`go*`): GET 遷移。
- Unsafe (`do*`、`unsafe`): POST。idempotent な書き込みは `idempotent` を使います。

## どうコードに流れ込むか

```
profile.json → semantic-ex Phase 1 (Experience)   → var/fake/*.json
             → semantic-ex Phase 2 (Examples)     → var/fake/observations.md
             → semantic-ex Phase 3 (Constraints)  → var/json_schema/*.json
             → HAL resources                      → src/Resource/App/*
```

ALPS の transition 名は HAL `_links` rel / Resource URI 規約と 1:1 で対応します。
たとえば `goArticle` → `app://self/article`、`doCreateArticle` →
`POST app://self/article` のように。

## profile の検証

```bash
asd --validate var/alps/profile.json
```

現在の profile は 0 errors / 0 warnings で validate されます。

## 可視化

```bash
asd var/alps/profile.json               # HTML state diagram
asd var/alps/profile.json -f mermaid    # Mermaid classDiagram
```
