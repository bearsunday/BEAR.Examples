# `json-schema-generated`

**fake observationからJSON Schemaを生成する** · [← 索引に戻る](../index.md)

- **Category:** Semantic / generated artifacts
- **Status:** `support`
- **Aliases:** generated schema, JSON Schema, semantic-ex constraints, response schema, validation schema, スキーマ生成, 制約導出, 観測
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** 実例データから観察した制約をschemaとして固定したい。

## 例

### 導出規則（gen-schemas.php）

制約は前もって決めず、`var/fake/*.json` の観測値から導出する。観測最大長は 50/100/200/500/1000… の nice 境界へ切り上げる:

```php
function ceilToNice(int $n): int
{
    foreach ([50, 100, 200, 500, 1000, 2000, 5000, 10000] as $boundary) {
        if ($n <= $boundary) {
            return $boundary;
        }
    }

    return (int) (ceil($n * 1.5 / 1000) * 1000);
}
```

文字列フィールドは `minLength` = 観測最小長、`maxLength` = nice 境界へ切り上げ:

```php
$obs = observeStr(fields($records, $key));
$minLen = ($obs['max_len'] === 0 && $obs['nulls'] > 0) ? 0 : $obs['min_len'];
$maxLen = ($obs['max_len'] === 0 && $obs['nulls'] > 0) ? 100 : ceilToNice($obs['max_len']);
```

再生成は `composer schema`（`php bin/semantic-ex/gen-schemas.php`）。決定的なので diff で変化を追える。

### 生成されるresponse schema

`var/json_schema/article.json` — 観測から導出した制約が固定される:

```json
"slug": {
    "type": "string",
    "description": "URL-safe unique slug",
    "minLength": 3,
    "maxLength": 50,
    "pattern": "^[a-z0-9][a-z0-9-]*$"
},
"title": {
    "type": "string",
    "description": "Article headline",
    "minLength": 10,
    "maxLength": 50
}
```

### 手書きのinput validation schema

`var/json_validate/article_create.json` は生成対象ではない — ajv-errors 形式の `errorMessage` 付きで手書き維持する:

```json
"slug": {
  "type": "string", "minLength": 3, "maxLength": 100, "pattern": "^[a-z0-9][a-z0-9-]*$",
  "errorMessage": {
    "pattern": "Slug must contain only lowercase letters, digits and hyphens.",
    "minLength": "Slug must be at least 3 characters.",
    "maxLength": "Slug must be at most 100 characters."
  }
}
```

### Resourceへの接続

`#[JsonSchema]` の `schema:` に生成response schema、`params:` に手書きinput validation schemaを指定する:

```php
#[JsonSchema('article.json')]
public function onGet(int $id): static

#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]
public function onPost(/* ... */): static
```

## Naming

Schemaファイルは2系統あり、置き場所と命名で区別する:

| 種別 | 置き場所 | 命名 | 例 |
|---|---|---|---|
| response schema（生成） | `var/json_schema/` | `<entity>.json` / `<entity>List.json` | `article.json`, `articleList.json` |
| input validation schema（手書き） | `var/json_validate/` | `<entity>_<action>.json` | `article_create.json`, `article_update.json` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 制約を「前もって決める」のではなく fake observation から導出すると理解したか。
- [ ] 生成対象はentity/list **response schema**（`var/json_schema/` の9件）であり、`var/json_validate/*.json`（input validation schema）と `write_response.json` 等は手書き維持と理解したか。

## Source

- [`bin/semantic-ex/gen-schemas.php`](../bin/semantic-ex/gen-schemas.php)
- [`var/json_schema/article.json`](../var/json_schema/article.json)
- [`var/json_schema/articleList.json`](../var/json_schema/articleList.json)
- [`var/json_validate/article_create.json`](../var/json_validate/article_create.json)
- [`var/json_validate/article_update.json`](../var/json_validate/article_update.json)

## Tests

- [`tests/Smoke/ResourceSmokeTest.php`](../tests/Smoke/ResourceSmokeTest.php)
- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`](../tests/Validation/JsonSchemaRequestExceptionHandlerTest.php)

## Key points

生成response schemaとinput validation schema（手書き）をResourceの `#[JsonSchema]` に接続する。導出規則: `maxLength` は観測最大長×1.5を50/100/200/500/1000…のnice境界へ切り上げ、`minLength` は観測最小長。`ResourceSmokeTest` が全GET resourceを宣言schemaに対して検証する。

## Do not

- 生成対象の `var/json_schema/*.json` を手で編集しない — `composer schema` で上書きされる。制約を変えたいなら fake data（→ [`semantic-fake-data`](./semantic-fake-data.md)）か導出規則側を変えて再生成する。

## マスター確認（After）

- [ ] `composer schema` で生成対象のresponse schemaが再生成され、Resourceの `#[JsonSchema]` 参照と一致。
- [ ] body変更時にschema更新を伴い `ArticleTest.php` 相当が green。

## See also

- [`semantic-fake-data`](./semantic-fake-data.md) — 観測元となるfake dataを決定的に生成する
- [`json-schema-validation`](./json-schema-validation.md) — `#[JsonSchema]` によるvalidationとエラーハンドリング
- [`alps-profile-ssot`](./alps-profile-ssot.md) — 意味定義のSSOTとなるALPS profile
- [`apidoc-llms-generated`](./apidoc-llms-generated.md) — 同じくsemantic層から生成するAPIドキュメント
- [`api-post-input-dto`](./api-post-input-dto.md) — `article_create.json` が検証するPOST入力
- [`api-put-tristate-input`](./api-put-tristate-input.md) — `article_update.json` が検証するPUT入力
