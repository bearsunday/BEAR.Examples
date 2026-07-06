# `json-schema-validation`

**Request/ResponseをJSON Schemaで検証する** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** `#[JsonSchema]`, response schema, params schema, validation, json_validate, json_schema, JSONスキーマ, スキーマ検証, 入力検証, バリデーション
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** Resourceの入力と出力のshapeを宣言的に固定したい。

## 例

### Resource

読み取り — response schema のみ（第1引数が `schema:`）:

```php
#[JsonSchema('article.json')]
public function onGet(int $id): static
```

書き込み — response は `schema:`、request params は `params:` に分ける:

```php
#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]
public function onPost(#[Input] ArticleCreateInput $input): static

#[JsonSchema(schema: 'write_response.json', params: 'article_update.json')]
public function onPut(#[Input] ArticleUpdateInput $input): static
```

### Module

`JsonSchemaModule` に response schema と request params schema の2ディレクトリを渡す。request違反のhandlerを差し替えて、構造化エラーをfield別にまとめる:

```php
$this->install(new JsonSchemaModule(
    $this->appMeta->appDir . '/var/json_schema',
    $this->appMeta->appDir . '/var/json_validate',
));
$this->bind(JsonSchemaRequestExceptionHandlerInterface::class)
    ->to(JsonSchemaRequestExceptionHandler::class)
    ->in(Scope::SINGLETON);
```

### Response schema

`var/json_schema/article.json` — GET の body shape を固定する（抜粋）:

```json
{
    "type": "object",
    "required": ["id", "slug", "title", "body", "status", "authorId", "categoryId"],
    "properties": {
        "slug": {
            "type": "string",
            "minLength": 3,
            "maxLength": 50,
            "pattern": "^[a-z0-9][a-z0-9-]*$"
        },
        "status": {
            "type": "string",
            "enum": ["draft", "published"]
        }
    }
}
```

### Request params schema

`var/json_validate/article_create.json` — 制約の隣に `errorMessage` key（ajv-errors規約）を置き、field別メッセージのSSOTにする（抜粋）:

```json
{
  "type": "object",
  "required": ["slug", "title", "body", "authorId", "categoryId"],
  "errorMessage": {
    "required": {
      "slug": "Slug is required.",
      "title": "Title is required."
    }
  },
  "properties": {
    "slug": {
      "type": "string", "minLength": 3, "maxLength": 100, "pattern": "^[a-z0-9][a-z0-9-]*$",
      "errorMessage": {
        "pattern": "Slug must contain only lowercase letters, digits and hyphens."
      }
    }
  }
}
```

## Naming

Schema ファイルは種別ごとに置き場所と命名が決まっている:

| 種別 | 置き場所 | 例 |
|---|---|---|
| Response schema | `var/json_schema/<entity>.json` | `article.json` |
| Request params schema | `var/json_validate/<entity>_<verb>.json` | `article_create.json`, `article_update.json` |

書き込み応答が同形のendpointは共有の `write_response.json` を使い、返す内容が違うendpointは専用schema（例: `auth_response.json`）を切る。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] response schema は `schema:`、request params schema は `params:` に分けると理解したか。
- [ ] DTO境界とvalidation schemaを同じ項目集合で揃えると決めたか。

## Source

- [`src/Resource/App/Article.php`](../src/Resource/App/Article.php)
- [`src/Module/AppModule.php`](../src/Module/AppModule.php)
- [`var/json_schema/article.json`](../var/json_schema/article.json)
- [`var/json_validate/article_create.json`](../var/json_validate/article_create.json)
- [`var/json_validate/article_update.json`](../var/json_validate/article_update.json)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Validation/JsonSchemaRequestExceptionHandlerTest.php`](../tests/Validation/JsonSchemaRequestExceptionHandlerTest.php)

## Key points

response schemaは `schema:`、request params schemaは `params:`。DTOとschemaは同じ境界を守る。違反は起点で例外が分かれる — request違反は `JsonSchemaRequestException`（400・client error）、response違反は `JsonSchemaResponseException`（500・server bug）。schemaの `errorMessage` key（ajv-errors規約）がfield別メッセージのSSOT。`#[JsonSchema]` には `key:`（bodyのindex key）と `target: 'view'`（描画後representationを検証）のオプションもある。

## Do not

- Resource body shapeをテストやschemaなしで暗黙に変えない — response schema違反はclient errorではなく `JsonSchemaResponseException`（500）として実行時に噴くため、shape変更はschema更新とセットで行う。

## マスター確認（After）

- [ ] method に `#[JsonSchema(schema: ..., params: ...)]` が付き、対応する `var/json_schema` / `var/json_validate` ファイルが存在。
- [ ] body shape を変えた時は schema も更新され、`ArticleTest.php` 相当が green。

## See also

- [`api-get-hal-resource`](./api-get-hal-resource.md) — response schema (`schema:`) が載る GET resource の基本形
- [`api-post-input-dto`](./api-post-input-dto.md) — `params:` schema と対になる Input DTO の入力境界
- [`api-put-tristate-input`](./api-put-tristate-input.md) — tri-state 入力（`article_update.json` の `tagIds`）の schema 表現
- [`json-schema-generated`](./json-schema-generated.md) — schema を fake data から生成する
- [`error-status-mapping`](./error-status-mapping.md) — 例外 → HTTP status の対応づけ
- [`form-validation-webform`](./form-validation-webform.md) — Page 層で validation 失敗を 422 form 再描画にする
