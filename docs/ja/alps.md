# ALPS profile

[English](../alps.md)

[var/alps/profile.json](../../var/alps/profile.json) は CMS のセマンティクス
における source of truth です。

## レイヤー

| Layer        | Example                                                        |
|--------------|----------------------------------------------------------------|
| Ontology     | `articleTitle`、`articleStatus`、`categoryParentId`、`mediaAlt`、`file` |
| Taxonomy     | `Article`、`ArticleList`、`Category`、`Tag`、`Author`、`Media` |
| Choreography | `goArticleList`、`goArticle`、`doCreateArticle`、`doUploadMediaFile` |

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

Media には意図的に 2 種類の write transition があります。`doCreateMedia` は
既存 URL の metadata 登録、`doUploadMediaFile` は `app://self/media-upload`
で `#[InputFile]` による binary upload を示します。

## profile の検証

`composer doc` は必要に応じて npm ASD dependency を install し、ALPS HTML/SVG
生成物を再生成します。

```bash
npm run alps:validate
```

現在の profile は 0 errors / 0 warnings で validate されます。

## 可視化

```bash
npm run doc:alps                        # HTML and static SVG diagrams
npx --no-install asd -f mermaid var/alps/profile.json
```
