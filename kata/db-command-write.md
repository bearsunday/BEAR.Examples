# `db-command-write`

**DB書き込みをCommand Interfaceに分ける** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `canonical`
- **Aliases:** write command, command interface, POST, PUT, DELETE, add update delete, CQRS split, AffectedRows, InsertedRow, 書き込み, 更新系, コマンド分離
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** ResourceからDBの作成、更新、削除を行いたい。

## 例

### CommandInterface

write は `<Entity>CommandInterface` に分け、method は命令形。戻り値は `void`:

```php
interface ArticleCommandInterface
{
    #[DbQuery('article_add')]
    public function add(
        string $slug,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
        int $authorId,
        int $categoryId,
    ): void;

    #[DbQuery('article_update')]
    public function update(
        int $id,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
    ): void;

    #[DbQuery('article_delete')]
    public function delete(int $id): void;
}
```

### 戻り値型で挙動が切り替わる

同じ SQL id でも宣言した戻り値型だけで挙動が変わる。影響行数が要るなら `AffectedRows`（`src/Query/Samples/`）:

```php
#[DbQuery('article_update')]
public function update(
    int $id,
    string $title,
    string $body,
    string|null $excerpt,
    string $status,
    string|null $publishedAt,
): AffectedRows;

#[DbQuery('article_delete')]
public function delete(int $id): AffectedRows;
```

影響行数が要る遷移系 write の実例 — 並行する publish/delete との競合を影響行数で区別する:

```php
#[DbQuery('article_publish')]
public function publish(int $id, string $status, string $publishedAt): AffectedRows;
```

### Resource

POST は add → 自然キーで id 回収 → 201 + `Location`:

```php
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

    $this->code = Code::CREATED;
    $this->headers['Location'] = '/article?id=' . $created->id;
    $this->body = [
        'id' => $created->id,
        'slug' => $input->slug,
    ];

    return $this;
}
```

DELETE は 404 ガード → delete → 204:

```php
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

### SQL

placeholder 名が method の引数名と対応する:

```sql
INSERT INTO articles (slug, title, body, excerpt, status, published_at, author_id, category_id)
VALUES (:slug, :title, :body, :excerpt, :status, :publishedAt, :authorId, :categoryId)
```

```sql
DELETE FROM articles WHERE id = :id
```

## Naming

Read と Write は interface を分ける — どちらも `src/Query/` に置く:

| 種別 | Interface | 例 |
|---|---|---|
| Read | `<Entity>QueryInterface` | `ArticleQueryInterface` |
| Write | `<Entity>CommandInterface` | `ArticleCommandInterface` |

Write の method 命名は命令形で、SQL ファイル名と対応:

| 形 | メソッド | SQL ファイル |
|---|---|---|
| 単一行 write | `add` / `update` / `delete` | `<entity>_add.sql` / `<entity>_update.sql` / `<entity>_delete.sql` |

Resource の依存プロパティ名は read と write で非対称にする:

| 依存 | プロパティ | 例 |
|---|---|---|
| `<Entity>QueryInterface` | `$<entity>` | `$article` |
| `<Entity>CommandInterface` | `$<entity>Cmd` | `$articleCmd` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] read を `<Entity>QueryInterface`、write を `<Entity>CommandInterface` に分離すると決めたか。
- [ ] write method を `add` / `update` / `delete` の命令形にすると決めたか。
- [ ] write method の戻り値を `void`（または `AffectedRows`）にする前提を理解したか。
- [ ] Resource依存名は read=queryable noun（`$article`）、write=`Cmd` 接尾辞（`$articleCmd`）にすると決めたか。

## Source

- [`src/Resource/App/Article.php::onPost()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Article.php::onPut()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Article.php::onDelete()`](../src/Resource/App/Article.php)
- [`src/Query/ArticleCommandInterface.php`](../src/Query/ArticleCommandInterface.php)
- [`src/Query/Samples/ArticleAffectedRowsCommandInterface.php`](../src/Query/Samples/ArticleAffectedRowsCommandInterface.php)
- [`var/db/sql/article_add.sql`](../var/db/sql/article_add.sql)
- [`var/db/sql/article_update.sql`](../var/db/sql/article_update.sql)
- [`var/db/sql/article_delete.sql`](../var/db/sql/article_delete.sql)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Integration/ArticleMySQLTest.php`](../tests/Integration/ArticleMySQLTest.php)
- [`tests/Smoke/MediaQuerySamplesTest.php`](../tests/Smoke/MediaQuerySamplesTest.php)

## Key points

readは `<Entity>QueryInterface`、writeは `<Entity>CommandInterface` に分ける。write methodは `add`, `update`, `delete` の命令形。同じSQL idでも宣言した戻り値型だけで挙動が切り替わる：`void`=実行のみ / `AffectedRows`=影響行数 / `InsertedRow`=auto-increment idと解決済み値。影響行数が要る遷移系writeの実例は `ArticleCommandInterface::publish(): AffectedRows`。Ray.MediaQueryは `DateTimeInterface` 引数の自動SQL文字列変換や `ToScalarInterface` / `__toString()` による値オブジェクト変換も提供する（本リポジトリはwire値を `SqlDateTime::fromRfc3339()` で明示変換して渡す方式）。

## Do not

- `lastInsertId` で新規IDを回収しない — write は `void` を返し、INSERT後のIDは自然キーの `by<NaturalKey>` 再SELECTで取る（→ [`db-read-by-natural-key`](./db-read-by-natural-key.md)）。`lastInsertId` はdriver依存で、fakeでも再現しにくい。

## マスター確認（After）

- [ ] `<Entity>QueryInterface` に write method が、`<Entity>CommandInterface` に read method が混ざっていない。
- [ ] write method 名が命令形（add/update/delete）で SQL ファイル名と対応。
- [ ] POST/PUT/DELETE の各経路を `ArticleTest.php` 相当で green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — Read/Write分離の相手方（読み取りQuery）
- [`db-read-by-natural-key`](./db-read-by-natural-key.md) — INSERT後のID回収を自然キーで行う
- [`db-link-table-sync`](./db-link-table-sync.md) — tagIds等のリンクテーブルをclear+linkで同期
- [`api-post-input-dto`](./api-post-input-dto.md) — POST入力をInput DTOで受ける
- [`api-delete-no-content`](./api-delete-no-content.md) — DELETEの204 idiom
- [`state-transition-resource`](./state-transition-resource.md) — `publish(): AffectedRows` を使う遷移系リソース
- [`db-transactional`](./db-transactional.md) — 複数writeをトランザクションで包む
