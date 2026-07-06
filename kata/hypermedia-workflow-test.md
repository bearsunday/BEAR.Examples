# `hypermedia-workflow-test`

**Link/Embedを辿るworkflowをテストする** · [← 索引に戻る](../index.md)

- **Category:** Tests / fake
- **Status:** `support`
- **Aliases:** hypermedia test, HAL workflow, follow links, `_links`, `_embedded`, rel naming, `#[Depends]`, href, ワークフローテスト, 遷移テスト, ユーザーストーリー
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/test.html
- **Use when:** API clientがHAL linkやembedを使って遷移できることを固定したい。

## 例

### 基底クラス（`follow()`）

`AbstractWorkflowTestCase` — 遷移はすべて `href($rel, $vars, $ro)` 経由。URIハードコードはstoryの入口1回だけ:

```php
protected function follow(ResourceObject $ro, string $rel, array $vars = []): ResourceObject
{
    $next = $this->resource->href($rel, $vars, $ro);
    $this->assertSame(200, $next->code, "Following rel `{$rel}` should return 200");

    return $next;
}
```

### Story（`#[Depends]` 連鎖）

1クラス = 1ユーザーストーリー。class名がstoryのタイトル、method名がstep。testdox出力が上から下へ物語として読める。cross-entityのid（`authorId` → author の `id`）はstepで明示的に渡す:

```php
/** A reader picks a tag, opens one of its articles, and looks up the author. */
final class ReaderBrowsesByTagTest extends AbstractWorkflowTestCase
{
    public function testOpensTagList(): ResourceObject
    {
        $tags = $this->resource->get('app://self/tags');
        $this->assertSame(200, $tags->code);

        return $tags;
    }

    #[Depends('testOpensTagList')]
    public function testPicksATag(ResourceObject $tags): ResourceObject
    {
        return $this->follow($tags, 'goTag', ['id' => $tags->body['items'][0]['id']]);
    }

    // ...（testViewsArticlesUnderThatTag → testOpensAnArticle）

    #[Depends('testOpensAnArticle')]
    public function testLooksUpTheAuthor(ResourceObject $article): void
    {
        $author = $this->follow($article, 'goAuthor', ['id' => $article->body['authorId']]);

        $this->assertSame($article->body['authorId'], $author->body['id']);
    }
}
```

### POST起点のstory（`Location` からid回収）

`EditorManagesArticleTest` — hypermedia clientは新規Resourceの URL を推測できないので、`Location` headerからidを取り出して次stepへ渡す:

```php
#[Depends('testCreatesAnArticle')]
public function testReadsBackTheNewArticle(ResourceObject $created): ResourceObject
{
    $id = $this->idFromLocation((string) $created->headers['Location']);
    $read = $this->resource->get('app://self/article', ['id' => $id]);

    $this->assertSame('First draft', $read->body['title']);

    return $read;
}
```

### Envelope contract（層分離のpin）

`_embedded` は遷移対象ではない。Taxonomy名詞 / Choreography動詞の層分離は、storyとは別の contract test で pin する:

```php
$rendered = json_decode((string) $article, true);

$this->assertIsArray($rendered);
$this->assertSame(['author', 'category', 'tagList'], array_keys($rendered['_embedded']));
// `self` is added by the HAL renderer; the rest are the resource's own choreography.
$rels = array_values(array_diff(array_keys($rendered['_links']), ['self']));
$this->assertSame(['goArticleList', 'goAuthor', 'goCategory'], $rels);
```

## Naming

Story class と step method の命名:

| 対象 | 形 | 例 |
|---|---|---|
| Story class | `<Actor><Verb>Test` | `ReaderBrowsesByTagTest`, `EditorManagesArticleTest` |
| Step method | 三人称現在の叙述。actorはclass名が持つのでstepでは省く | `testOpensTagList`（`testReaderOpensTagList` ではない） |
| Contract pin | `*ContractTest` — storyとは別class | `HalEnvelopeContractTest` |

辿る rel は ALPS Choreography 名（`goArticle`, `goAuthor`）、`_embedded` は Taxonomy 名詞（`author`, `tagList`）。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] Resource単体のbody assertだけでなく、rel を辿る遷移可能性をテストすると決めたか。
- [ ] rel名の層分離（link=Choreography / embed=Taxonomy）を検証対象に含めると理解したか。

## Source

- [`tests/Hypermedia/AbstractWorkflowTestCase.php`](../tests/Hypermedia/AbstractWorkflowTestCase.php)
- [`tests/Hypermedia/ReaderBrowsesByCategoryTest.php`](../tests/Hypermedia/ReaderBrowsesByCategoryTest.php)
- [`tests/Hypermedia/ReaderBrowsesByTagTest.php`](../tests/Hypermedia/ReaderBrowsesByTagTest.php)
- [`tests/Hypermedia/EditorManagesArticleTest.php`](../tests/Hypermedia/EditorManagesArticleTest.php)
- [`docs/conventions.md`](../docs/conventions.md)

## Tests

- [`tests/Hypermedia/HalEnvelopeContractTest.php`](../tests/Hypermedia/HalEnvelopeContractTest.php)

## Key points

1クラス = 1ユーザーストーリー。各stepは `#[Depends]` で前stepの ResourceObject を受け取り、`follow()`（内部は `$resource->href($rel, $vars, $ro)`）で `_links` の rel を解決して遷移する — URIハードコード遷移は禁止。`_embedded` は遷移対象ではなくenvelope contract（`HalEnvelopeContractTest` が層分離をassert）。POST起点のstoryは `Location` headerからidを取り出して次stepへ渡す。

## Do not

- vars を省略して body-merge に頼らない — `Anchor::href()` は source body を URI template に merge するので `follow($article, 'goAuthor')` は動く*ように見える*が、article 自身の `id` が author の `id` slot に流れ込み、別の author が 200 で返る。cross-entity の id は `['id' => $article->body['authorId']]` と明示的に渡す。

## マスター確認（After）

- [ ] テストが `_links` の rel を `href()` で解決して次Resourceへ遷移している。
- [ ] reader/editor の代表workflowが `HalEnvelopeContractTest.php` 相当で green。

## See also

- [`hal-link`](./hal-link.md) — 辿る対象の `_links` を `#[Link]` で宣言する側
- [`hal-embed`](./hal-embed.md) — contract testがpinする `_embedded` の宣言側
- [`alps-profile-ssot`](./alps-profile-ssot.md) — rel名（Choreography / Taxonomy）のSSOT
- [`app-resource-test`](./app-resource-test.md) — endpoint単体をassertするResourceテスト（workflowテストの相手方）
- [`fake-sql-query`](./fake-sql-query.md) — DBなしでworkflowテストを走らせるfake
- [`api-post-input-dto`](./api-post-input-dto.md) — `Location` headerを返すPOSTの型
