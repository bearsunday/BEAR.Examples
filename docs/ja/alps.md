# ALPS profile

[English](../alps.md)

[var/alps/profile.json](../../var/alps/profile.json) は CMS のセマンティクス
における source of truth です。public HTML の閲覧、Page/Admin の認証と
記事管理フロー、HAL App resource API を含む CMS 全体の状態空間を記述します。

## レイヤー

| Layer        | Example                                                        |
|--------------|----------------------------------------------------------------|
| Ontology     | `articleTitle`、`articleStatus`、`categoryParentId`、`mediaAlt` |
| Taxonomy     | `Home`、`Article`、`ArticleList`、`AdminLogin`、`AdminArticleForm`、`Media` |
| Choreography | `goArticleList`、`goAdminLogin`、`doCompleteLogin`、`doAdminCreateArticle` |

- Safe (`go*`): GET 遷移。
- Unsafe (`do*`、`unsafe`): POST。idempotent な書き込みは `idempotent` を使います。

## どうコードに流れ込むか

```
profile.json → semantic-ex Phase 1 (Experience)   → var/fake/*.json
             → semantic-ex Phase 2 (Examples)     → var/fake/observations.md
             → semantic-ex Phase 3 (Constraints)  → var/json_schema/*.json
             → HAL resources                      → src/Resource/App/*
             → Page resources                     → src/Resource/Page/*
```

App API では、ALPS の transition 名は HAL `_links` rel / Resource URI 規約と
1:1 で対応します。たとえば `goArticle` → `app://self/article`、
`doCreateArticle` → `POST app://self/article` のように。

Page/Admin では、ブラウザで観測できる状態遷移を記録します。たとえば
未ログイン admin access → `goAdminLogin`、OAuth callback →
`doCompleteLogin`、記事フォーム送信 → `doAdminCreateArticle` /
`doAdminUpdateArticle` です。

## profile の検証

```bash
asd --validate var/alps/profile.json
```

現在の profile は 0 errors / 0 warnings / 0 suggestions で validate されます。

## 可視化

```bash
asd var/alps/profile.json               # HTML state diagram
asd var/alps/profile.json -f mermaid    # Mermaid classDiagram
```
