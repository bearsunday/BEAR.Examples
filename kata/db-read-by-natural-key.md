# `db-read-by-natural-key`

**natural keyで1件読む** · [← 索引に戻る](../index.md)

- **Category:** Data access / BDR
- **Status:** `canonical`
- **Aliases:** natural key lookup, bySlug, byEmail, byFilename, after insert lookup, unique key read, 自然キー, 一意キー, INSERT後のID回収, lastInsertIdを使わない
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/database.html
- **Use when:** clientが指定した一意な値で再取得したい。特にINSERT後に新規IDを回収したい。

## 例

### QueryInterface

`by<NaturalKey>()` — 引数はclientが指定した一意な値、戻り値は `<Entity>|null`:

```php
#[DbQuery('author_by_email')]
public function byEmail(string $email): Author|null;
```

```php
#[DbQuery('media_by_filename')]
public function byFilename(string $filename): Media|null;
```

enum・日付正規化が要るEntityは [`db-entity-factory`](./db-entity-factory.md) と同様に `factory:` を指定する:

```php
#[DbQuery('article_by_slug', factory: ArticleFactory::class)]
public function bySlug(string $slug): Article|null;
```

### Resource（INSERT後のID回収）

Commandの書き込みは `void` — 新規IDは `lastInsertId()` ではなく、clientが渡した自然キーで再SELECTして回収する:

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

INSERT直後の `bySlug()` がnullを返すのは一意制約かreplica-lagの異常 — 握りつぶさず `assert` で表面化させ、5xxにする。

### SQL

[`db-read-one-entity`](./db-read-one-entity.md) の `article_item.sql` とはWHERE句だけが違う — カラム順は同じくhydration先の引数順:

```sql
SELECT
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at,
    author_id,
    category_id
FROM articles
WHERE slug = :slug
LIMIT 1
```

## Naming

method名とSQLファイル名が同じ語彙で対応する:

| 形 | メソッド | SQL ファイル |
|---|---|---|
| 自然キーで1件 | `by<NaturalKey>()` | `<entity>_by_<key>.sql` |

例: `bySlug` ↔ `article_by_slug.sql`、`byEmail` ↔ `author_by_email.sql`、`byFilename` ↔ `media_by_filename.sql`。

`item`（主キー）と `by<NaturalKey>`（自然キー）は意図的に別形 — PKは技術的なIDのハンドル、slug / email / filename はドメイン上の意味を持つ代替キー。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 対象Entityに slug / email / filename のような自然キー（一意制約）があるか確認したか。
- [ ] 取得methodを `by<NaturalKey>()`（`bySlug`/`byEmail`/`byFilename`）と命名すると決めたか。
- [ ] INSERT後の新規ID回収を `lastInsertId()` ではなく自然キー再SELECTで行う方針を理解したか。
- [ ] INSERT直後の `by<NaturalKey>()` がnullを返す異常系は `assert` で表面化させる方針か確認したか（`Article::onPost()` の `assert($created !== null)` 参照）。

## Source

- [`src/Query/ArticleQueryInterface.php::bySlug()`](../src/Query/ArticleQueryInterface.php)
- [`src/Resource/App/Article.php::onPost()`](../src/Resource/App/Article.php)
- [`var/db/sql/article_by_slug.sql`](../var/db/sql/article_by_slug.sql)
- [`src/Query/AuthorQueryInterface.php::byEmail()`](../src/Query/AuthorQueryInterface.php)
- [`var/db/sql/author_by_email.sql`](../var/db/sql/author_by_email.sql)
- [`src/Query/MediaQueryInterface.php::byFilename()`](../src/Query/MediaQueryInterface.php)

## Tests

- [`tests/Resource/App/ArticleTest.php`](../tests/Resource/App/ArticleTest.php)
- [`tests/Smoke/FakeSqlQueryTest.php`](../tests/Smoke/FakeSqlQueryTest.php)

## Key points

INSERT後は `lastInsertId()` ではなく、`bySlug()` などのnatural keyで再SELECTする。Ray.MediaQueryには `InsertedRow` 戻り値（auto-increment id回収）もあるが、本Kataの正規経路はdriver非依存でFakeでも決定的な自然キー再SELECT（[docs/conventions.md §4 After-INSERT id](../docs/conventions.md#after-insert-id)）。

## Do not

- `lastInsertId()` で新規IDを回収しない — driver依存のID状態をResourceの標準経路に持ち込まない。他フレームワークでは常套手段だが、自然キー再SELECTはdriver非依存でFakeでも決定的に動く。

## マスター確認（After）

- [ ] write path に `lastInsertId` が登場しない（`grep -ri lastinsertid src/` が空）。
- [ ] `by<Key>()` method と `<entity>_by_<key>.sql` が対応して存在。
- [ ] POST後に自然キーで再取得し新規IDを body へ返すフローを `ArticleTest.php` 相当で green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — 主キーで1件読む基本形（`item()`）
- [`db-command-write`](./db-command-write.md) — 書き込みCommandは `void` を返す — 本KataでID回収が要る理由
- [`db-entity-factory`](./db-entity-factory.md) — `bySlug` の `factory:` 指定（enum・日付正規化）
- [`api-post-input-dto`](./api-post-input-dto.md) — `onPost` のInput DTO（POSTフロー全体）
- [`fake-sql-query`](./fake-sql-query.md) — 自然キー再SELECTがFakeでも決定的に動く仕組み
