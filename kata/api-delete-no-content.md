# `api-delete-no-content`

**DELETE成功を204で返す** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** DELETE resource, no content, 204, delete command, not found before delete, 削除API, 冪等削除
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** App Resourceで削除操作を公開したい。

## 例

### Resource

削除前に存在確認 — 未存在は404、成功は `Code::NO_CONTENT` と空body:

```php
#[Purge(uri: 'app://self/articles')]
public function onDelete(int $id): static
{
    if ($this->article->item($id) === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $id];

        return $this;
    }

    $this->articleCmd->delete($id);
    $this->code = Code::NO_CONTENT;
    $this->body = [];

    return $this;
}
```

### CommandInterface

書き込みは `void` を返す:

```php
#[DbQuery('article_delete')]
public function delete(int $id): void;
```

### SQL

```sql
DELETE FROM articles WHERE id = :id
```

## Naming

Write は Read の `<Entity>QueryInterface` と interface を分け、method は命令形動詞:

| 種別 | 命名 | 例 |
|---|---|---|
| Write interface | `<Entity>CommandInterface` | `ArticleCommandInterface` |
| 削除 method | 命令形動詞 | `delete(int $id): void` |
| SQL ファイル | `<entity>_delete.sql` | `article_delete.sql` |
| Resource property | `$<entity>Cmd` | `$this->articleCmd->delete($id)` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 削除前に存在確認を行い、未存在は404にすると決めたか。
- [ ] 成功時は `Code::NO_CONTENT`（204）と空bodyを返すと決めたか。

## Source

- [`src/Resource/App/Article.php::onDelete()`](../src/Resource/App/Article.php)
- [`src/Query/ArticleCommandInterface.php::delete()`](../src/Query/ArticleCommandInterface.php)
- [`var/db/sql/article_delete.sql`](../var/db/sql/article_delete.sql)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)

## Key points

削除前に存在確認し、成功時は `Code::NO_CONTENT` と空body。`onDelete` は `#[Purge(uri: 'app://self/articles')]` を伴い、キャッシュ済みcollectionをwrite時に無効化する（[`cache-purge`](./cache-purge.md) 参照）。

## Do not

- 削除済みや未存在を成功扱いにしない — DELETEの冪等性を「未存在でも204」と読みがちだが、この型では存在確認して404を返す。

## マスター確認（After）

- [ ] 成功時 `$this->code = Code::NO_CONTENT` かつ body が空。
- [ ] 未存在IDの削除が404になることを `ArticleTest.php` 相当で green。
- [ ] 削除後の同一id GETが404になるround-tripも pin する。

## See also

- [`db-command-write`](./db-command-write.md) — Command interface と書き込みSQLの基本形
- [`api-post-input-dto`](./api-post-input-dto.md) — 作成側（POST → 201 + Location）
- [`api-put-tristate-input`](./api-put-tristate-input.md) — 更新側（PUT → 200、未存在404）
- [`not-found-response`](./not-found-response.md) — 404のidiom
- [`cache-purge`](./cache-purge.md) — `#[Purge]` によるwrite時のcollection無効化
- [`hypermedia-workflow-test`](./hypermedia-workflow-test.md) — create → … → delete → 404 のライフサイクルをstoryで検証する
