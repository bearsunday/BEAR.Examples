# `apidoc-llms-generated`

**API docsとllms.txtを生成する** · [← 索引に戻る](../index.md)

- **Category:** Semantic / generated artifacts
- **Status:** `support`
- **Aliases:** ApiDoc, OpenAPI, llms.txt, docs generation, `composer doc`, API documentation, Tool Use, AI instrument, APIドキュメント, ドキュメント自動生成
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/apidoc.html
- **Use when:** Resource、schema、ALPSから人間向け・AI向けのAPI資料を生成したい。

## 例

### apidoc.xml

生成フォーマットは `<format>` が決める。入力は Resource に加えて ALPS profile（→ [`alps-profile-ssot`](./alps-profile-ssot.md)）と fake data（→ [`semantic-fake-data`](./semantic-fake-data.md)）:

```xml
<apidoc xsi:noNamespaceSchemaLocation="./vendor/bear/api-doc/apidoc.xsd">
    <appName>BEAR\Kata</appName>
    <scheme>app</scheme>
    <docDir>docs</docDir>
    <format>html,openapi,llms,audit,terms</format>
    <alps>var/alps/profile.json</alps>
    <fakeData dir="docs/examples"/>
    <title>BEAR.Kata API</title>
</apidoc>
```

### composer doc

`composer doc` が fake data のコピー → apidoc 実行 → ALPS HTML 生成を束ねる（後段に audit.md / HTML の整形もある）:

```json
"doc": [
    "@setup:docs",
    "mkdir -p docs/examples",
    "cp var/fake/*.json docs/examples/",
    "./vendor/bin/apidoc",
    "npm run doc:alps --silent"
]
```

### 生成物: openapi.json

`operationId` は HAL rel（`goArticle` 等）、response schema と example は `$ref` で参照される:

```json
"/article": {
    "operationId": "goArticle",
    "responses": {
        "200": {
            "content": {
                "application/json": {
                    "schema": {
                        "$ref": "#/components/schemas/Article"
                    },
                    "examples": {
                        "fake": {
                            "$ref": "#/components/examples/ArticleFake"
                        }
                    }
```

### 生成物: llms.txt

Routes / ResourceObjects / Responses / Query Interfaces / SQL / Entities を1ファイルに集約する:

```
## Routes (23)

| HTTP Route | Methods | Resource |
|------------|---------|----------|
| /article | GET, POST, PUT, DELETE | App/Article |
| /articles | GET | App/Articles |

## Query Interfaces (14)

| Interface | Methods |
|-----------|---------|
| ArticleQueryInterface | item(id):Article, bySlug(slug):Article, list(categoryId, tagId, authorId, status, perPage):PagesInterface |
```

## Naming

生成物には既存の命名規則がそのまま出る — 生成物の可読性は命名の一貫性に依存する:

| 生成物 | 由来する命名 |
|---|---|
| `openapi.json` の `operationId` | HAL rel（ALPS Choreography）— `goArticle`, `doCreateArticle` |
| `llms.txt` の Query Interfaces | `item` / `by<Key>` / `list` の method 命名 |
| `llms.txt` の SQL | `<entity>_<verb>.sql` のファイル命名 |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] ドキュメントを手書きで固定せず、Resource/schema/ALPS から生成すると決めたか。
- [ ] 出力の役割分担を理解したか — 人間向けは `docs/index.html`（ApiDoc HTML）、ツールチェーン統合用は `docs/openapi.json`（OpenAPI 3.1）、AI向け入口は `docs/llms.txt`。

## Source

- [`apidoc.xml`](../apidoc.xml)
- [`docs/openapi.json`](../docs/openapi.json)
- [`docs/llms.txt`](../docs/llms.txt)
- [`docs/index.html`](../docs/index.html)
- [`docs/audit.md`](../docs/audit.md)
- [`composer.json`](../composer.json)

## Tests

- なし（生成物を検証する自動テストは無い。`composer doc` 後の `git diff docs/` を目視確認）

## Key points

`composer doc` がApiDocとALPS HTMLを生成する。生成フォーマットは `apidoc.xml` の `<format>`（本リポジトリは html / openapi / llms / audit / terms）が決める。llms.txtにはAPI endpoint / ResourceObject / Query・Command interface / SQL / entity定義が含まれ、URI・型・schemaがそのままAIのtool定義（Tool Use / MCP instrument）に使える。

## Do not

- 生成物（`docs/openapi.json` / `docs/llms.txt` / `docs/index.html`）を手で直さない — 次の `composer doc` で上書きされる。手書きドキュメントだけを正とせず、Resource / schema / ALPS 側を修正して再生成し、同期を保つ。

## マスター確認（After）

- [ ] `composer doc` でAPI資料が再生成され、Resource/schemaと同期する。
- [ ] 生成物（openapi.json / llms.txt）が現在のルートとResponseを反映している。

## See also

- [`alps-profile-ssot`](./alps-profile-ssot.md) — 生成の意味的SSOTとなるALPS profile
- [`semantic-fake-data`](./semantic-fake-data.md) — docs/examples に取り込まれる fake data の生成元
- [`json-schema-generated`](./json-schema-generated.md) — openapi.json が参照する response schema の生成
- [`json-schema-validation`](./json-schema-validation.md) — schema を Resource に結びつける `#[JsonSchema]`
- [`tool-use-instrument`](./tool-use-instrument.md) — llms.txt をAIのtool定義（instrument）として使う
