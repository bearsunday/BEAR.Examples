# `api-put-tristate-input`

**PUTでtri-state入力を扱う** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** PUT resource, update resource, tri-state, nullable array, tagIds, leave clear replace, 更新API, 3状態入力, タグ置換, 省略時維持
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** 省略、空配列、非空配列で異なる意味を持つ更新入力を扱いたい。

## 例

### Input DTO

native な `array|null $tagIds = null` で受ける — 省略/`null` は「維持」、array は `array_values()` で list へ正規化:

```php
final readonly class ArticleUpdateInput
{
    /** @var list<int>|null null = leave existing links, [] = clear, non-empty list = replace. */
    public array|null $tagIds;

    /** @param array<array-key, int>|null $tagIds */
    public function __construct(
        #[Input]
        public int $id,
        #[Input]
        public string $title,
        #[Input]
        public string $body,
        #[Input]
        public string $status,
        #[Input]
        public string|null $excerpt = null,
        #[Input]
        public string|null $publishedAt = null,
        #[Input]
        array|null $tagIds = null,
    ) {
        if ($tagIds === null) {
            $this->tagIds = null;

            return;
        }

        /** @var list<int> $normalised */
        $normalised = array_values($tagIds);
        $this->tagIds = $normalised;
    }
}
```

### Resource

`null` のときだけ tag 同期をスキップ（維持）。`[]` は `syncTags` に渡り clear だけで終わる（全削除）、非空 list は置換:

```php
#[JsonSchema(schema: 'write_response.json', params: 'article_update.json')]
public function onPut(#[Input] ArticleUpdateInput $input): static
{
    if ($this->article->item($input->id) === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $input->id];

        return $this;
    }

    $this->articleCmd->update(
        $input->id,
        $input->title,
        $input->body,
        $input->excerpt,
        $input->status,
        $this->sqlDateTime->fromRfc3339($input->publishedAt),
    );

    if ($input->tagIds !== null) {
        $this->syncTags($input->id, $input->tagIds);
    }

    $this->code = Code::OK;
    $this->body = ['id' => $input->id];

    return $this;
}
```

```php
private function syncTags(int $articleId, array $tagIds): void
{
    $this->articleTagCmd->clear($articleId);
    foreach ($tagIds as $tagId) {
        $this->articleTagCmd->link($articleId, $tagId);
    }
}
```

### Validation schema

schema 側も同じ tri-state を宣言する。update 側（`article_update.json`）は `null` を許す:

```json
"tagIds": {"type": ["array", "null"], "items": {"type": "integer", "minimum": 1}}
```

create 側（`article_create.json`）は `"type": "array"` で `null` 不可:

```json
"tagIds": {"type": "array", "items": {"type": "integer", "minimum": 1}}
```

## Naming

| 種別 | 命名 | 例 |
|---|---|---|
| Input DTO | `<Entity><Verb>Input` | `ArticleUpdateInput` |
| validation schema | `var/json_validate/<entity>_<verb>.json` | `article_update.json` |
| link-table write method | 命令形動詞 `clear` / `link` | `ArticleTagCommandInterface::clear()` / `::link()` |
| link-table SQL | `<link>_clear.sql` / `<link>_link.sql` | `article_tag_clear.sql` / `article_tag_link.sql` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 「省略（維持）/ 空（全削除）/ 非空（置換）」の3状態を区別する必要があるか確認したか。
- [ ] `null` と `[]` を同一視しないと決めたか。DTOはnativeな `array|null` typed parameterで受けると決めたか。

## Source

- [`src/Resource/App/Article.php::onPut()`](../src/Resource/App/Article.php)
- [`src/Input/ArticleUpdateInput.php`](../src/Input/ArticleUpdateInput.php)
- [`var/json_validate/article_update.json`](../var/json_validate/article_update.json)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)

## Key points

`tagIds === null` は維持、`[]` は全削除、listは置換。DTOはnative `array|null $tagIds` で受け、constructorで `array_values()` によりlistへ正規化する。非arrayのmalformed入力はRay.InputQueryがresource boundaryで `ParameterException`（400）として拒否する。validation schema側も同じtri-stateを宣言する — `article_update.json` は `"tagIds": {"type": ["array", "null"]}`（create側は `"type": "array"` でnull不可）。

## Do not

- `null` と `[]` を同じ意味に潰さない — `$tagIds ?? []` と書いた瞬間に「省略=維持」が消え、tagIds を送らない更新が全タグ削除になる。

## マスター確認（After）

- [ ] DTO が `null` / `[]` / 非空list の3分岐を保持している。
- [ ] 非空リストでの置換と、非array入力の400拒否を `ArticleTest.php` 相当で green（省略=維持 / `[]`=全削除も自分の実装ではテストに含めるとよい）。

## See also

- [`api-post-input-dto`](./api-post-input-dto.md) — POST側のInput DTO（create側、`tagIds` はnull不可）
- [`db-link-table-sync`](./db-link-table-sync.md) — `clear` + `link` によるタグ置換の実装（`syncTags` の中身）
- [`json-schema-validation`](./json-schema-validation.md) — `#[JsonSchema(params:)]` によるresource boundaryのvalidation
- [`api-patch-partial-update`](./api-patch-partial-update.md) — 差分適用で更新するPATCHの型
- [`api-delete-no-content`](./api-delete-no-content.md) — 同じArticleのDELETE
- [`not-found-response`](./not-found-response.md) — `onPut` 冒頭の存在確認404のidiom
