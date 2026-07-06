# `db-link-table-sync`

**link tableをclear/linkで同期する** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `canonical`
- **Aliases:** many-to-many, tagIds, link table, clear links, replace relation, article tags, 多対多, 中間テーブル, 関連テーブル, リレーション置換
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** 記事とタグのような関連テーブルを、入力されたIDリストに置き換えたい。

## 例

### CommandInterface

link table専用のCommandに `clear` と `link` の2 methodを置く:

```php
interface ArticleTagCommandInterface
{
    /** Drop every tag link for an article (used before a fresh replace). */
    #[DbQuery('article_tag_clear')]
    public function clear(int $articleId): void;

    /** Link one article ↔ tag pair. Resource layer loops over a tagIds list. */
    #[DbQuery('article_tag_link')]
    public function link(int $articleId, int $tagId): void;
}
```

### Resource

置換は「全削除→再リンク」— `clear()` の後に `link()` をループする:

```php
/**
 * Replace the article's tag links with the given tag id list.
 *
 * @param list<int> $tagIds
 */
private function syncTags(int $articleId, array $tagIds): void
{
    $this->articleTagCmd->clear($articleId);
    foreach ($tagIds as $tagId) {
        $this->articleTagCmd->link($articleId, $tagId);
    }
}
```

呼び出し側は tri-state 入力と連動する — `onPut()` では `null` なら触らない:

```php
if ($input->tagIds !== null) {
    $this->syncTags($input->id, $input->tagIds);
}
```

### SQL

`article_tag_clear.sql`:

```sql
DELETE FROM article_tags WHERE article_id = :articleId
```

`article_tag_link.sql`:

```sql
INSERT INTO article_tags (article_id, tag_id) VALUES (:articleId, :tagId)
```

### FakeSqlQuery

write SQL id は `WRITE_SQL_IDS` allowlist に登録する（`DbQueryInterceptor` はwriteも getRow/getRowList 経由で呼ぶ）:

```php
private const array WRITE_SQL_IDS = [
    // ...
    'article_tag_clear',
    'article_tag_link',
];
```

## Naming

link tableのCommandは親子ペアで命名する:

| 種別 | 命名 | 例 |
|---|---|---|
| Interface | `<Parent><Child>CommandInterface` | `ArticleTagCommandInterface` |
| Method（write動詞） | `clear` / `link` | `clear(int $articleId)` / `link(int $articleId, int $tagId)` |
| SQL ファイル | `<link>_clear.sql` / `<link>_link.sql` | `article_tag_clear.sql` / `article_tag_link.sql` |
| Resource property | `$<entity><Role>` | `private ArticleTagCommandInterface $articleTagCmd` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 多対多の更新を「全削除→再リンク」の置換戦略で行うと決めたか。
- [ ] link用Commandに `clear($parentId)` と `link($parentId, $childId)` を用意すると決めたか。
- [ ] link tableのSQLをResourceに書かず `<rel>_clear.sql` / `<rel>_link.sql` に置くと決めたか。
- [ ] 新しいwrite SQL id（`<rel>_clear` / `<rel>_link`）を `tests/Fake/FakeSqlQuery.php` の `WRITE_SQL_IDS` allowlistへ登録すると理解したか（`DbQueryInterceptor` はwriteもgetRow/getRowList経由で呼ぶ）。

## Source

- [`src/Resource/App/Article.php::syncTags()`](../src/Resource/App/Article.php)
- [`src/Query/ArticleTagCommandInterface.php`](../src/Query/ArticleTagCommandInterface.php)
- [`var/db/sql/article_tag_clear.sql`](../var/db/sql/article_tag_clear.sql)
- [`var/db/sql/article_tag_link.sql`](../var/db/sql/article_tag_link.sql)
- [`tests/Fake/FakeSqlQuery.php`](../tests/Fake/FakeSqlQuery.php)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Integration/ArticleMySQLTest.php`](../tests/Integration/ArticleMySQLTest.php)

## Key points

relation更新は `clear($articleId)` 後に `link($articleId, $tagId)` を繰り返す。PUTでは tri-state入力と連動する：`tagIds === null` は触らない、`[]` は全解除、リスト指定は置換（[`api-put-tristate-input`](./api-put-tristate-input.md) 参照）。

## マスター確認（After）

- [ ] Resource に link table の `INSERT`/`DELETE` 文字列が無い。
- [ ] 更新フローが `clear()` → `link()` ループになっている。
- [ ] tagIds を別リストに変更した時に関連が置換されることを `ArticleTest.php`（tagIds `[1,2,3]`→`[4,5]` の置換ケース）相当で green。

## See also

- [`api-put-tristate-input`](./api-put-tristate-input.md) — `tagIds` の `null` / `[]` / リストの3状態入力
- [`api-post-input-dto`](./api-post-input-dto.md) — 新規作成時に tagIds を受けるInput DTO
- [`db-command-write`](./db-command-write.md) — 書き込みCommandの基本形
- [`db-transactional`](./db-transactional.md) — 複数の書き込みをトランザクションで包む
- [`fake-sql-query`](./fake-sql-query.md) — write SQL id をFakeで処理する仕組み
- [`mysql-integration-test`](./mysql-integration-test.md) — 実DBでlink table置換を検証する
