# `api-post-input-dto`

**POST入力をInput DTOで受ける** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** POST resource, create resource, Input DTO, `#[Input]`, Ray.InputQuery, request DTO, 入力DTO, 作成API, 201 Created, Location header
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** 入力項目が多い作成処理を、Resource methodの前でDTO化したい。

## 例

### Input DTO

`final readonly class`、各constructor parameterに `#[Input]`。配列入力はconstructorで `array_values()` 正規化して `list<int>` に閉じる:

```php
final readonly class ArticleCreateInput
{
    /** @var list<int> */
    public array $tagIds;

    public function __construct(
        #[Input]
        public string $slug,
        #[Input]
        public string $title,
        #[Input]
        public string $body,
        #[Input]
        public int $authorId,
        #[Input]
        public int $categoryId,
        #[Input]
        public string|null $excerpt = null,
        #[Input]
        public string $status = 'draft',
        #[Input]
        public string|null $publishedAt = null,
        #[Input]
        array $tagIds = [],
    ) {
        $normalised = array_values($tagIds);
        $this->tagIds = $normalised;
    }
}
```

### Resource

method parameterは `#[Input] <Dto>` の1つだけ。成功時は 201 + `Location`、新idは `bySlug` で回収する:

```php
#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]
public function onPost(#[Input] ArticleCreateInput $input): static
{
    $this->articleCmd->add(
        $input->slug,
        $input->title,
        $input->body,
        $input->excerpt,
        $input->status,
        $this->sqlDateTime->fromRfc3339($input->publishedAt),
        $input->authorId,
        $input->categoryId,
    );

    $created = $this->article->bySlug($input->slug);
    assert($created !== null);
    if ($input->tagIds !== []) {
        $this->syncTags($created->id, $input->tagIds);
    }

    $this->code = Code::CREATED;
    $this->headers['Location'] = '/article?id=' . $created->id;
    $this->body = [
        'id' => $created->id,
        'slug' => $input->slug,
    ];

    return $this;
}
```

### Validation schema

per-field制約（regex・enum・長さ）は `var/json_validate/article_create.json` が持つ:

```json
{
  "type": "object",
  "required": ["slug", "title", "body", "authorId", "categoryId"],
  "properties": {
    "slug": {
      "type": "string", "minLength": 3, "maxLength": 100, "pattern": "^[a-z0-9][a-z0-9-]*$"
    },
    "status": {
      "type": "string", "enum": ["draft", "published"]
    },
    "tagIds": {"type": "array", "items": {"type": "integer", "minimum": 1}}
  }
}
```

## Naming

| 対象 | 命名 | 例 |
|---|---|---|
| Input DTO | `src/Input/<Entity><Verb>Input.php` | `ArticleCreateInput` |
| validation schema | `var/json_validate/<entity>_<verb>.json` | `article_create.json` |
| 書き込みCommand method | 命令形動詞 | `add`, `update`, `delete` |
| INSERT後のid回収 | `by<NaturalKey>()` | `bySlug` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 入力が「多数 / tri-state / まとまった名前付きshape」のどれかで、DTO化が妥当か判断したか（短いflat入力なら scalar parameter のままでよい。ただし単一値でも `trim()` 等の正規化をconstructorに閉じたい場合はDTO化してよい）。
- [ ] DTOを `src/Input/<Entity><Verb>Input.php` に置き、`#[Input]` で受けると決めたか。

## Source

- [`src/Resource/App/Article.php::onPost()`](../src/Resource/App/Article.php)
- [`src/Input/ArticleCreateInput.php`](../src/Input/ArticleCreateInput.php)
- [`var/json_validate/article_create.json`](../var/json_validate/article_create.json)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)

## Key points

Resource method parameterに `#[Input] ArticleCreateInput $input` を置く。schema validationは `#[JsonSchema(params: ...)]`。失敗モードは2層 — 必須fieldの欠落はDTO生成時の `ParameterException`、schema違反（pattern等）は `ValidationException`（いずれも400）。成功時は `Code::CREATED`（201）+ `Location` header、新idは自然キー再SELECTで回収（[`db-read-by-natural-key`](./db-read-by-natural-key.md)）。Ray.InputQueryはネストDTOや `#[Input(item: ...)]` のobject array入力にも対応する。作成後のresource本体を201 bodyで返したい場合はbear/packageの `#[ReturnCreatedResource]` がLocationの内部GETを自動で行う。

## Do not

- 多数の関連する入力を無理にflat scalar parameterへ増やし続けない — このcodebaseでは6 field（`Media::onPost`）がscalarのまま読める上限として意図的に置かれている。それを超えたら、あるいは配列・tri-stateが混ざったらDTOに切り替える。

## マスター確認（After）

- [ ] method signature が `#[Input] <Entity>CreateInput $input` になっている。
- [ ] DTOの境界と `*_create.json` validation schema が同じ項目集合を守る。
- [ ] 正常作成（201 + Location）と検証エラー（必須欠落 / pattern違反）の両方を `ArticleTest.php` 相当で green。

## See also

- [`api-put-tristate-input`](./api-put-tristate-input.md) — 更新側のDTO。tri-state（`null`/`[]`/list）を型で運ぶ
- [`db-command-write`](./db-command-write.md) — onPostが呼ぶ書き込みCommandの型
- [`db-read-by-natural-key`](./db-read-by-natural-key.md) — INSERT後のid回収（`bySlug`）
- [`db-link-table-sync`](./db-link-table-sync.md) — `tagIds` のlink table同期（clear + link）
- [`json-schema-validation`](./json-schema-validation.md) — `params:` schemaによる入力検証の詳細
- [`file-upload-input`](./file-upload-input.md) — ファイルを含む入力の `#[InputFile]` 版
