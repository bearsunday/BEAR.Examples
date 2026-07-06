# `state-transition-resource`

**状態遷移を独立Resourceとして切り出す** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** state machine, state transition, draft published, ArticlePublish, 409 Conflict, AffectedRows, 状態遷移, 公開, 下書きから公開, 競合検出
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** リソースの状態遷移（draft→published等）をフィールド編集(PUT)とは別のResourceとして切り出したい。

## 例

### Resource

遷移専用のResource（`app://self/article-publish`）。未存在なら404、既に目標状態なら409、遷移成功で200:

```php
public function onPost(int $id, string|null $publishedAt = null): static
{
    $article = $this->article->item($id);
    if ($article === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $id];

        return $this;
    }

    if ($article->isPublished()) {
        $this->code = 409;
        $this->body = [
            'message' => 'Article is already published',
            'id' => $id,
            'status' => $article->status->value,
        ];

        return $this;
    }

    $effectiveAt = $publishedAt ?? gmdate('Y-m-d\\TH:i:s\\Z');
    $publishedAtSql = (string) $this->sqlDateTime->fromRfc3339($effectiveAt);
    $publishedAtUtc = (string) $this->sqlDateTime->toRfc3339Utc($effectiveAt);
    $affectedRows = $this->articleCmd->publish(
        $id,
        ArticleStatus::Published->value,
        $publishedAtSql,
    );
    if (! $affectedRows->isAffected()) {
        return $this->publishConflict($id);
    }

    $this->code = Code::OK;
    $this->body = [
        'id' => $id,
        'slug' => $article->slug,
        'status' => ArticleStatus::Published->value,
        'publishedAt' => $publishedAtUtc,
    ];

    return $this;
}
```

### 競合時の再読

`isAffected()` false は事前チェック後に並行publish/deleteが割り込んだ印。再読して409/404に振り分ける:

```php
private function publishConflict(int $id): static
{
    $article = $this->article->item($id);
    if ($article === null) {
        $this->code = Code::NOT_FOUND;
        $this->body = ['message' => 'Article not found', 'id' => $id];

        return $this;
    }

    $this->code = 409;
    $this->body = [
        'message' => 'Article is already published',
        'id' => $id,
        'status' => $article->status->value,
    ];

    return $this;
}
```

### CommandInterface

`update()` とは別methodにし、`AffectedRows` で遷移の成否を返す:

```php
#[DbQuery('article_publish')]
public function publish(int $id, string $status, string $publishedAt): AffectedRows;
```

### SQL

原子性の本体。`AND status = 'draft'` がcheck-then-actの隙をDB側で閉じる:

```sql
UPDATE articles
SET status = :status,
    published_at = :publishedAt
WHERE id = :id
  AND status = 'draft'
```

## Naming

状態遷移は書き込み（verb-form）の一種 — 命令形の動詞で一貫させる:

| 対象 | 形 | 例 |
|---|---|---|
| 遷移Resource | `<Entity><Verb>` | `ArticlePublish` → `app://self/article-publish` |
| Command method | 命令形の動詞 | `publish(int $id, ...): AffectedRows` |
| SQL ファイル | `<entity>_<verb>.sql` | `article_publish.sql` |
| Command property | `$<entity>Cmd` | `private ArticleCommandInterface $articleCmd` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] フィールド編集と状態遷移を別Resourceに分け、遷移専用のURIを持たせると決めたか。
- [ ] 既に目標状態にある場合は409 Conflictを返し、再実行を安全にすると決めたか。
- [ ] `AffectedRows` で原子性を判定し、競合を検出すると理解したか。

## Source

- [`src/Resource/App/ArticlePublish.php`](../src/Resource/App/ArticlePublish.php)
- [`src/Query/ArticleCommandInterface.php::publish()`](../src/Query/ArticleCommandInterface.php)
- [`var/db/sql/article_publish.sql`](../var/db/sql/article_publish.sql)
- [`var/json_validate/article_publish.json`](../var/json_validate/article_publish.json)

## Tests

- [`tests/Resource/App/ArticlePublishTest.php`](../tests/Resource/App/ArticlePublishTest.php)

## Key points

状態遷移は独立Resource（`ArticlePublish`）。原子性の本体はSQL guard — `article_publish.sql` は `WHERE id = :id AND status = 'draft'` でcheck-then-actの隙をDB側で閉じる。`isAffected()` falseは並行publish/deleteを意味し、再読して409/404に振り分ける。`publishedAt` 省略時は現在UTC、明示ISO-8601指定でbackdate可（応答はRFC3339 UTCに正規化）。

## Do not

- 既に目標状態の再遷移を200で成功扱いしない — retry安全に見えて、状態機械としては「遷移していない」事実を隠す。409で状態を正直に返す。

## マスター確認（After）

- [ ] draft→published で 200 + publishedAt が返る。
- [ ] 既に published の再publishで 409、未存在idで 404 が返ることを `ArticlePublishTest.php` 相当で green。

## See also

- [`api-put-tristate-input`](./api-put-tristate-input.md) — フィールド編集側のPUT（状態遷移はここに混ぜない）
- [`db-command-write`](./db-command-write.md) — 書き込みCommand Interfaceの基本形
- [`json-schema-validation`](./json-schema-validation.md) — `article_publish.json` による入力検証
- [`not-found-response`](./not-found-response.md) — 404のidiom
- [`error-status-mapping`](./error-status-mapping.md) — 例外→HTTPステータスの写像（本Kataはcodeを直接設定する形）
- [`admin-confirm-page`](./admin-confirm-page.md) — この遷移を確認画面Pageからラップする
