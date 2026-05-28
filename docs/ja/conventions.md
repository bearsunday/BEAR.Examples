# 規約

[English](../conventions.md)

「このコードベースでどうコードを書くか」の cross-cutting なルールです。
アーキテクチャやパターンの説明は [architecture.md](architecture.md) にあります。
このファイルは、構築中に下した *決定事項* を成文化する companion です
(元の議論ログは [../journal/decisions-to-consult.md](../journal/decisions-to-consult.md)
にあります)。

迷ったときはここに従ってください。新しい規約はまずここに着地し、それから
コードや他のドキュメントが追従します。

## 目次

1. [コード構造](#1-コード構造) — namespace、ディレクトリ配置、Read/Write 分離
2. [Contexts](#2-contexts) — `hal-api-app` / `cli-` / `fake-` / `test-` の構成
3. [命名](#3-命名) — class、query メソッド、resource property、SQL ファイル名、ALPS、HAL rel
4. [Resource パターン](#4-resource-パターン) — body 構築、status code、INSERT 後の id、ページネーション、**input shape & validation**、例外、named arguments、メソッド順序
5. [Read/Write SQL contract](#5-readwrite-sql-contract) — カラム順、fetch mode、書き込み id 検出
6. [ファイル / データ配置](#6-ファイルデータ配置) — `var/` の成果物配置
7. [テスト](#7-テスト) — context wiring、hermetic な fake、assertion スタイル
8. [プロセス](#8-プロセス) — 規約の採用、deprecate された規約の引退

---

## 1. コード構造

| What | Convention |
|------|-----------|
| Namespace root | `MyVendor\Cms` |
| Layer ディレクトリ | `src/Entity/`、`src/Query/`、`src/Resource/App/`、`src/Module/`、`src/Service/` |
| Fake の配置 | `tests/Fake/` — `composer.json` は `MyVendor\Cms\` を `src/` と `tests/` の両方にマッピング (autoload + autoload-dev) しているので、`fake-hal-api-app` (dev) と `test-hal-api-app` (test) のどちらでも `MyVendor\Cms\Fake\*` を解決できます。Production (`composer install --no-dev`) では `tests/` をロードしないので、prod 成果物に fake binding が混入しません |
| Module 構成 | `FakeModule` が binding を提供し、`TestModule` が `FakeModule` を *install* します。prod / cli / fake / test の各 context が異なる構成を取れるよう二段階にしています |
| Resource 配置 | `src/Resource/App/<Class>.php` — すべての URI が class です。"/" entry-point に意味がない限り `App/Index.php` は作りません |
| Read/Write 分離 | entity ごとに必ず 2 つの interface を作ります: `<Entity>QueryInterface` (Read) と `<Entity>CommandInterface` (Write)。両方とも `src/Query/` に置き、interface 名の suffix で Read/Write の区別を担うことで、`MediaQuerySqlModule` が単一ディレクトリをスキャンできます。Read と Write を同じ interface に混在させてはいけません |
| MediaQuery result 配置 | `src/Result/*` は `src/Query/*Interface` method から返される型付き Ray.MediaQuery result object の置き場です。これは domain entity ではなく、query execution context や DML metadata を包む object です。read query では query-local projection、つまり特定の `#[DbQuery]` 結果から組み立てる型付き read-side view として扱います。controller / service helper ではありません。このディレクトリは query result 専用に保ち、`src/Query` と `src/Result` が読みやすい対になるようにします |

### Variation resources

`src/Resource/App/Variations/` には、比較目的のみの Article GET 実装が ちょうど 3 つ
入っています。これらは ALPS profile に登録されておらず、canonical な
`src/Resource/App/Article.php` のパスを変更してはいけません。

| Variation | 軸 | 答える問い |
|---|---|---|
| [`Variations\ArticleAsArray`](../../src/Resource/App/Variations/ArticleAsArray.php) | データ表現 (entity vs array) | "entity class を作る価値はあるのか？" |
| [`Variations\ArticleSqlQuery`](../../src/Resource/App/Variations/ArticleSqlQuery.php) | 抽象レベル (declarative `#[DbQuery]` vs programmatic `SqlQuery` class) | "`#[DbQuery]` で済まないときはどうするか？" |
| [`Variations\ArticleRawPdo`](../../src/Resource/App/Variations/ArticleRawPdo.php) | フレームワークの有無 (MediaQuery vs raw `ExtendedPdoInterface`) | "MediaQuery は実際何をしてくれているのか？" |

4 つ目の variation を追加してはいけません。代替設計を比較したいときは
`composer demo:variations` を使い、`composer demo` はメインの golden path として
保ちます。短い読みガイドは
[`src/Resource/App/Variations/README.md`](../../src/Resource/App/Variations/README.md)
または
[`README.ja.md`](../../src/Resource/App/Variations/README.ja.md)
を参照してください。

## 2. Contexts

| Context | どこで動くか |
|---------|--------------|
| `hal-api-app` | Production HTTP |
| `cli-hal-api-app` | `bin/app.php`、`composer app`、`bin/cli/*` スクリプト |
| `fake-hal-api-app` | `FakeSqlQuery` に対する Dev runtime (DB なし) — `composer fake`、手動の動作確認など |
| `test-hal-api-app` | PHPUnit (`FakeModule` を構成) |

`fake-` と `test-` が canonical な prefix です。バリエーションを勝手に作らないでください。

## 3. 命名

### Class / interface
- Read interface: `<Entity>QueryInterface` (例: `ArticleQueryInterface`)
- Write interface: `<Entity>CommandInterface` (例: `ArticleCommandInterface`)
- Entity: public プロパティのみを持つ `final readonly class`

### Query / Command メソッド名
**Read は名詞形 (queryable な名詞 + 修飾)、Write は動詞形 (命令形のアクション)
を使います。** 下にある SQL ファイル名と同じ語彙なので、
`#[DbQuery('article_item')] public function item(int $id)` は attribute と
signature の間で同じ言語を話します。

| 種類 | メソッドの形 | 例 |
|---|---|---|
| 主キーによる単行 read | `item` | `item(int $id)` |
| 自然キーによる単行 read | `by<NaturalKey>` | `bySlug`、`byEmail`、`byFilename` |
| 複数行 read | `list` (variant: `list<Variant>`) | `list()`、`listByArticle(int $articleId)` |
| 単行 write | 命令形動詞 | `add`、`update`、`delete` |
| Link テーブルの write | 命令形動詞 | `clear`、`link` (例: `ArticleTagCommandInterface`) |

`item` (canonical な PK lookup) と `by<NaturalKey>` (代替アクセスパス) は意図的に
異なる形にしています: PK は技術的な identity ハンドル、自然キー
(`slug`、`email`、`filename`) はドメインで意味のある代替手段です。この非対称性
が両者の役割の違いをエンコードします。

`item` ↔ `list` は語彙的なペアを成し、BEAR の resource shape をミラーします:
`Article` (item resource) ↔ `Articles` (collection resource);
`item($id)` ↔ `list(...)`。

INSERT のあと、新しい行は `lastInsertId` ではなく `by<NaturalKey>` で自然キー
経由で取得します。自然キーは client が渡したものなので、re-SELECT すれば
driver 依存の状態に頼らずに付与された id を返せます。

### Resource property 名

Resource は Query / Command interface への依存を保持します。Read は
**queryable な名詞**、Write は **アクションのツール** です — それに応じて命名
します:

| 依存 | property パターン | 例 |
|---|---|---|
| 主 entity の `<Entity>QueryInterface` | `$<entity>` | `private ArticleQueryInterface $article` |
| 主 entity の `<Entity>CommandInterface` | `$<entity>Cmd` | `private ArticleCommandInterface $articleCmd` |
| 補助 / link entity の interface | `$<entity><Role>` | `private ArticleTagCommandInterface $articleTagCmd` |

非対称な命名が情報を運びます:

- `$this->article->item($id)` は「article ソースの item を id で」と読めます —
  receiver が queryable な名詞で、メソッドが query を修飾します。Rails の
  `Article.find(id)` と syntax は違いますが役割としてはミラーしています。
- `$this->articleCmd->add(...)` は「article command、add せよ」と読めます —
  receiver はツール、メソッドはアクション名です。

単一 entity に焦点を当てた Resource (`Article`、`Author` 等) では、suffix なしの
property 名は read 役を主 entity に予約し、補助的な write-only リンクと区別します。

### SQL ファイル名
- パターン: `var/db/sql/` 配下の `<entity>_<verb>.sql`
- 動詞は上のメソッド名と一致させます:
  - `item` ↔ `<entity>_item.sql`
  - `by_<key>` ↔ `<entity>_by_<key>.sql`
  - `list` ↔ `<entity>_list.sql`、`<entity>_list_by_<x>.sql`
  - `add` / `update` / `delete` ↔ 同じ
  - link テーブル動詞 ↔ `<link>_clear.sql`、`<link>_link.sql`
- 例: `article_item.sql`、`article_by_slug.sql`、
  `article_list.sql`、`article_add.sql`、`article_update.sql`、
  `article_delete.sql`、`article_tag_clear.sql`、`article_tag_link.sql`

### ALPS Ontology
- Entity prefix を付けます: `articleId`、`articleSlug`、`articleTitle`、
  `categoryParentId`、`mediaAlt`。`id` / `slug` 単体ではいけません
  (entity 間で衝突するリスクがあります)。

### HAL rel 命名 — ALPS layer で分割する
これは決定的なルールです。ALPS には 2 つの層があり、HAL にも 2 つの collection
(`_links` と `_embedded`) があります。揃えてください:

| どこ | 元の layer | 例 |
|------|-----------|-----|
| `#[Link]` rel | ALPS **Choreography** (transition 動詞) | `goArticleList`、`goAuthor`、`doCreateArticle`、`doDeleteTag` |
| `#[Embed]` rel | ALPS **Taxonomy** (entity 名詞) | `author`、`category`、`tagList` |

混ぜないでください: `#[Embed(rel: 'goAuthor', ...)]` は誤りです — `go*` は
Choreography (client が follow できる遷移) ですが、embed は server が含める
taxonomy インスタンスだからです。名前空間を分けて保ってください。

## 4. Resource パターン

### Body 構築
`$this->body` は `ResourceObject` の唯一の出力 channel です。`Embed` interceptor
は `onGet` が走る *前* に `Request` オブジェクトを `$this->body[$rel]` に注入
します。したがって:

- **`#[Embed]` ありの `onGet`**: `+=` (no-overwrite union) を使います。これは
  embed が注入したスロットを保護し、意図を明示的にします
  (「自分のデータを足し、もとからあるものには触れない」):
  ```php
  $this->body['author']->addQuery(['id' => $article->authorId]);
  $this->body['category']->addQuery(['id' => $article->categoryId]);
  $this->body['tagList']->addQuery(['articleId' => $article->id]);

  $this->body += [
      'id' => $article->id,
      'slug' => $article->slug,
      // ...
  ];
  ```
- **`#[Embed]` なしの `onGet`、`onPost`、`onPut`、`onDelete`、エラーパス**:
  リテラルの `$this->body = [...]`。shape が JSON として上から下まで読めます。
- **逐次的な `$this->body['k'] = $v;` は使いません。** これは多数の行に
  response shape を散らし、`+=` やリテラルに対して何の semantic 上の利点も
  ありません。

理由: `+` (「union」) は「上書きしない」であり、「左を優先で勝たせる」では
ありません。entity 自身のフィールドが embed rel と衝突しえないとき (§3 で
強制 — embed は taxonomy 名詞、body field は scalar)、`+=` は意味的に最も
精密な演算子になります。

### Page テンプレート not-found パターン

id で単一の primary entity をロードする Page resource は、404 を返すとき
`body = ['message' => '<X> not found']` をセットします。4xx でも Qiq
template は呼び出されるため、ガードなしで null entity のプロパティを参照
すると警告が出ます。テンプレート先頭で entity ごとのドメイン例外を投げる
ことで、フレームワークの `catch (Throwable)` 経路が `templates/Error.php`
にルーティングします。

```php
<?php
/**
 * @var \MyVendor\Cms\Entity\Article|null $article
 */
if (! isset($article) || $article === null) {
    throw new \MyVendor\Cms\Exception\ArticleNotFoundException();
}
?>
```

例外は entity ごと (`ArticleNotFoundException`、`AuthorNotFoundException`
など) で、既存の `MyVendor\Cms\Exception\*NotFoundException` 系列に揃えます。
ガードが必要なのは *primary* entity のみ。list 形式の変数は常に list
(空かもしれない) であり、null ではありません。

該当する Page test には `testNotFoundRendersErrorTemplate` を必ず加え、
警告のリグレッションを検出します。

### Status code

| Method | 成功 | 見つからない | 検証失敗 (App 層) | 検証失敗 (Page 層) |
|--------|------|-------------|--------------------|--------------------|
| GET | 200 | 404 | n/a | n/a |
| POST (リソースを作成する) | 201 + `Location` ヘッダ | n/a | `ValidationException` を throw | 422 + form 再描画 |
| POST (アクション / 非作成) | 200 + body | n/a | `ValidationException` を throw | 422 + form 再描画 |
| PUT | 200 | 404 | `ValidationException` を throw | 422 + form 再描画 |
| DELETE | 204 | 404 | n/a | n/a |
| 重複 `slug` (または他の unique key) | — | — | — | 409 (DB の `UniqueConstraintViolation` 経由、手動 catch なし) |
| POST (状態遷移、すでに目的状態) | — | — | 409 + `{message, id, status}` (例: `ArticlePublish` を public 済み article に対して) | — |

App リソースは検証失敗を `ValidationException` (`field => list<string>`
を保持) として throw します。422 body にはなりません — `app://` には
thrown error を HTTP status に変換する transfer layer が存在しないためです。
Page リソースは catch して 422 form を描画します。配線の詳細は
[validation-layer-design.md](../journal/validation-layer-design.md)
を参照してください。

**POST は常に作成ではありません。** `201 + Location` は POST が新しい
addressable resource を追加する場合に限ります (例: `Article::onPost` が
`/article?id=N` を作成)。新しい URI を作らない action 型 POST — 認証
code-for-session 交換、パスワードリセット確定、「このイベントを記録する」
endpoint — は結果 body と一緒に `200` を返し、`Location` ヘッダは付けません。
`#[JsonSchema(params:)]` の input 検証ルールはどちらの場合でも適用されます。

**状態遷移リソース** (例: `ArticlePublish`) はエンティティリソースの
メソッドではなく、別リソースとして並べます。URI が遷移を表現し、
エンティティリソースは verb-rich な CRUD に集中させます。詳細は
[`article-publish-flow-design.md`](../journal/article-publish-flow-design.md)
を参照してください。

### INSERT 後の id
`lastInsertId` を使ってはいけません (driver 依存で fake しにくい)。client が
渡した自然キー (slug / email / filename) を使い、`by<NaturalKey>` で再 SELECT
します。canonical な Resource 向け `Command` 側は `void` を返します。Resource
以外の caller が DML metadata を必要とする場合は、明示的な sample/read-model
command として分け、MediaQuery の `AffectedRows` を返します。例は
[MediaQuery サンプル](media-query-samples.md) を参照してください。

### ページネーション
Article collection read は Ray.MediaQuery の `#[Pager]` を使い、
`PagesInterface` を返します。Resource code は `$pages[$page]` を読み、返された
Page object の associative `data` rows を `ArticleFactory` に通し、`total`、
`hasNext`、`maxPerPage` を使います。DB なし fake は Pagerfanta の
`ArrayAdapter` で同じ contract を実装し、PDO-backed Pages がなくても同じ
pagination shape をテストします。

### Input shape & validation

このコードベースは意図的に 2 種類の入力 shape を混在させています — 一部の
endpoint は Resource 境界で DTO、別の endpoint は名前付き scalar parameter
を使います。endpoint ごとに **実際の signature shape が要求するもの** で
選び、画一的なルールでは選びません。パターンカタログがあえて短いのは、
教育的価値が「どのパターンが、どこで、なぜ適合するか」を見ることにあるから
です。

#### 現在の適用と理由

| Endpoint | Shape | Validation | 理由 |
|---|---|---|---|
| `Article::onPost` | `ArticleCreateInput` DTO | `#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]` | `tagIds` list を含む 9 フィールド — flat な signature では読めない。struct としてまとめる |
| `Article::onPut`  | `ArticleUpdateInput` DTO | `#[JsonSchema(schema: 'write_response.json', params: 'article_update.json')]` | tri-state `tagIds` を含む 7 フィールド (`null`/`[]`/list で replace semantics) — tri-state は型付きの carrier が必要 |
| `Auth::onPost`    | `AuthExchangeInput` DTO  | `#[JsonSchema(schema: 'auth_response.json', params: 'auth_exchange.json')]` | OAuth の `code`/`state` は意味のある struct で、無関係な 2 つの scalar ではない。フィールド数より読みやすさを優先 |
| `Author::onPost`  | scalar | `#[JsonSchema(params: 'author_create.json')]` | trivial な 3 フィールド。メソッド signature *が* contract |
| `Author::onPut`   | scalar | `#[JsonSchema(params: 'author_update.json')]` | 同上 |
| `Tag::onPost`     | scalar | `#[JsonSchema(params: 'tag_create.json')]`    | 2 フィールド |
| `Category::onPost`/`onPut` | scalar | `#[JsonSchema(params: 'category_*.json')]` | 4 フィールド、すべて独立した scalar |
| `Media::onPost`   | scalar | `#[JsonSchema(params: 'media_create.json')]`  | 6 フィールドだが、それぞれ独立したプロパティ。nest や tri-state はない — DTO 領域の境界線。「flat な list として読めるかどうか」の上限を見せるために意図的に scalar |

**DTO 形のメソッドは end-to-end で validate されます** — これは
[BEAR.Resource 1.31.1](https://github.com/bearsunday/BEAR.Resource/releases/tag/1.31.1)
([#356](https://github.com/bearsunday/BEAR.Resource/issues/356)) と
[BEAR.ApiDoc 1.9.1](https://github.com/bearsunday/BEAR.ApiDoc/releases/tag/1.9.1)
([#81](https://github.com/bearsunday/BEAR.ApiDoc/issues/81)) 以降の話です。
`JsonSchemaInterceptor` は `params:` schema で検証する前に `#[Input]` DTO の引数
を unpack するようになり、`var/json_validate/<entity>_<verb>.json` の制約
(slug 正規表現、status enum、長さ上限など) が resource 境界で強制されます。
`OpenApiGenerator` は対応する `requestBody` schema を出力し、openapi の contract
が同じ shape を反映します。診断の経緯は
[`../journal/decisions-to-consult.md`](../journal/decisions-to-consult.md) P8-#45
を参照してください。

#### Native array DTO input

BEAR.Resource 1.x-dev (Ray.InputQuery 1.1 経由) は Resource 境界の
`#[Input]` DTO で native な `array` / `array|null` constructor parameter
を扱えます。collection field には実際の型を使います:
`ArticleCreateInput::tagIds` は `array $tagIds = []`、tri-state update の
`ArticleUpdateInput::tagIds` は `array|null $tagIds = null` です。非 array の
不正な shape は Ray.InputQuery が拒否し、BEAR.Resource が
`ParameterException` (400 系) として wrap するため、DTO constructor には
到達しません。DTO 側は妥当な配列を `array_values()` で正規化するだけです。
`items` や `minimum` のような element ごとの制約は引き続き JSON Schema が
担当します。

`Auth::onPost` は共有の `write_response.json` (整数 DB id) ではなく専用の
`auth_response.json` (OAuth provider 由来の string subject id) を使います —
response schema は endpoint が実際に何を返すかで選び、テンプレートで選ばないでください。

#### 判断ルール (fit-driven)

新しい endpoint を設計するときは、パターン網羅を求めず、*shape* の必要に
基づいて判断します:

- **scalar に留める** — parameter list が短く flat で trivial、フィールド名が
  JSON Schema property と 1:1 にマップでき、nest や tri-state 構造がない場合。
  `#[JsonSchema(params: '<entity>_<verb>.json')]` が名前付き引数を validate し、
  メソッド signature *が* contract です。これがデフォルト。
- **Input DTO を使う** — 以下のいずれかが当てはまる場合:
  - parameter 数が読みやすさの閾値を超える (~7+ フィールド)
  - tri-state や部分更新 semantics を持つフィールドがある (例: `null` / `[]` /
    non-empty list で省略 ≠ 明示的な空)
  - フィールド群が、resource を超えて意味を持つ名前付き struct としてまとまる
    (例: OAuth callback ペア)

  `MyVendor\Cms\Input\<Action>Input` を `final readonly class` として定義し、
  各 constructor 引数に `#[Input]` を付け、resource 引数の型を
  `#[Input] <Dto>` にすると、BEAR.Resource の `InputParam`
  (`Ray\InputQuery\InputQueryInterface` 経由) が flat な request 配列から
  メソッド実行前にオブジェクトをマテリアライズします。Module install は不要 —
  `BEAR\Resource\Module\ResourceClientModule` で bind されます。

このコードベースの例: `src/Input/ArticleCreateInput.php`、
`ArticleUpdateInput.php`、`AuthExchangeInput.php`。それぞれ
`Article::onPost`、`Article::onPut`、`Auth::onPost` で消費されています。

#### なぜ DTO を Command interface に通さないのか

Ray.MediaQuery は `#[DbQuery]` interface で Input DTO を native にサポート
しています (`vendor/ray/media-query/src/ParamConverter.php::expandInputObjects()`
で確認可能。公式マニュアルにも記載:
https://bearsunday.github.io/manuals/1.0/ja/database_media.html#rayinputqueryとの連携)。
ここではあえて使いません。

理由 — ここの Resource 層は passthrough ではないからです。`Article::onPost` /
`onPut` は Command 境界で DTO を named scalar 引数に unpack します。Resource は
`syncTags()` も実行し、`bySlug` の round-trip もし、write/read の順序を入れ替える
こともあるためです。その作業を 1 回の DTO pass に隠すと、Resource が何をして
いるかを誤って表現してしまいます。unpack ステップ (~8 行) は、どのフィールドが
SQL に届き、どのフィールドが別の orchestration を駆動するか
(例: `tagIds` → `syncTags`、決して `article_update.sql` には bind しない)
を文書化する役割を担っています。

これは本コードベース固有の理由で Command 境界を scalar に保つ判断です。
Ray.MediaQuery の DTO サポートは、Resource-to-Command 境界が本当に passthrough
であるコードベースにとっては正しい選択肢として残ります。

`<Entity>CommandInterface` を per-resource な `Input` shape と結合させると、
§1 の Read/Write 層分離も消えてしまいます。call site での positional unpacking
は §4 の名前付き引数ルール (動詞-then-fields の明確な順序、リテラル bool なし、
途中スキップなし) と整合します。

### 例外
- 汎用の `LogicException` / `RuntimeException` は使いません。`src/` 由来で
  throw する例外は `MyVendor\Cms\Exception\<DomainName>Exception` を定義します。
- Read のエラー (見つからない) は throw せず、`$this->code` 経由で 404 を返します。
- リクエスト検証失敗は `MyVendor\Cms\Exception\ValidationException`
  (フレームワークの `JsonSchemaRequestException` ではない) を throw し、
  `field => list<string>` の map を保持します。
  `JsonSchemaRequestExceptionHandler` は BEAR.Resource の構造化された
  request schema error を field ごとに束ね、Page リソースが catch して
  422 form 再描画として surface します。Response schema の失敗は
  `JsonSchemaResponseException` (5xx 系) のままで、ユーザー入力エラーには
  変換しません。詳細は
  [validation-layer-design.md](../journal/validation-layer-design.md)
  を参照してください。

#### JSON Schema の `errorMessage` キー (ajv-errors 慣習)

`var/json_validate/*.json` の schema は constraint の隣に
`errorMessage` キーワードを置けます。constraint そのものは残ります —
`pattern: "^[a-z]+$"` は引き続き検証を行い、`errorMessage.pattern` は
ワイヤー上のコピーだけを所有します。

```json
"slug": {
  "type": "string",
  "pattern": "^[a-z0-9][a-z0-9-]*$",
  "errorMessage": {
    "pattern": "Slug must contain only lowercase letters, digits and hyphens."
  }
}
```

required フィールドのコピーは親側で
`errorMessage.required.<field>` に置きます。`errorMessage` は
プレーン文字列でも書けて、その場合はそのプロパティの任意の失敗に対する
fallback になります。

BEAR.Resource は validator row を `JsonSchemaError` に変換する時点で
これらの `errorMessage` template を解決します。このアプリ側の handler は
すでに描画済みの `$error->message` を使い、field ごとに group するだけです。

`errorMessage` は default validator メッセージが不自然な場合や、
管理画面の語彙に合わせる必要があるときにだけ追加してください — override
がない schema もそのまま動き、validator の default メッセージに
fall through します。

### 呼び出し側での名前付き引数
**positional がデフォルトです。** positional だと読み手が呼び出しを decode
できなくなる場合に限って named を使います。PHP 8.0 RFC は named arguments を
正確に 2 つの状況のために導入しました。私たちはその 2 つだけを採用し、それ以上
は採りません。

named を使う条件:

1. **リテラルの `true` / `false` を渡すとき。** `execute($sql, true, false)`
   は型や順序からは decode できません — signature を開かないと分かりません。
   これが RFC の旗艦事例です (Popov: "three booleans")。
   ```php
   // bad
   $query->execute($sql, true, false);
   // good
   $query->execute($sql, cache: true, strict: false);
   ```
   bool が *変数* 経由で渡される場合 (`$query->execute($sql, $useCache)`)、
   意味は変数名が運ぶので positional で構いません。

2. **途中の optional 引数をスキップするとき。** 欲しいものに到達するために
   default を埋めると、call の意図が消えてしまいます。
   ```php
   // bad
   htmlspecialchars($s, ENT_COMPAT | ENT_HTML401, 'UTF-8', false);
   // good
   htmlspecialchars($s, double_encode: false);
   ```

それ以外は positional のままで — 引数が多くても — 型と動詞の順序で call が
decode できる限り構いません:

```php
new Point($x, $y);
new Range($min, $max);
$fs->move($src, $dst);                    // direction-verb
$cache->remember($key, $ttl, $callback);
$client->request($method, $url, $options);
$command->add($slug, $title, $body, $excerpt, $status,
              $publishedAt, $authorId, $categoryId);
```

引数の数だけを理由に named を使ってはいけません。引数が多すぎて読みにくい
call は **signature の設計問題** であって call 側の問題ではありません。
設計を直してください。

呼び出し側での判断順:
1. リテラル `true`/`false` → named
2. 途中 optional をスキップ → named
3. それ以外 → positional

named がじわじわ増えてくるなら、signature が smell です:
- **Value object に集約する。** 引数の多い command は 1 つの DTO を取ります。
  「struct object」(Larry Garfield の用語) のコンストラクタは named の自然な
  居場所です。
- **`bool` parameter を捨てる。** `save()` / `forceSave()` に分けるか
  `enum SaveMode` を使います。PHPMD `BooleanArgumentFlag` も同じ理由で
  これをフラグします。
- **メソッドを分ける。** 「`true` / `false` で挙動が切り替わる」は SRP 違反
  の変装です。

スコープ: この規約は内部 interface (Read/Write 境界、Resource 層 call) を対象
とします。public ライブラリ API は範囲外です — そこではパラメータ名が BC
契約の一部になるので、代わりに `@no-named-arguments` (PHPStan / Psalm /
PHP-CS-Fixer) を検討してください。

明示的に **採用しない** ルール:
- 「3+ 引数 → named」 — `cache->remember($key, $ttl, $callback)` のような
  健全な call まで巻き込み、positional をデフォルトとする立場が崩れます。
- 「5+ 引数 → named」 — 数の閾値は設計問題を call site の syntax で隠します。
- 「同型 2+ → named」 — `Point(x, y)` や direction-verb の `move(src, dst)` まで
  巻き込んでしまいます。
- 「nullable parameter → named」 — `?string` 自体は swap バグを起こしません。
  *デフォルトをスキップする* ケースはルール 2 で既にカバーされています。
- 「`bool` parameter → named」 — 変数経由の `$force` は self-explaining です。
  問題はリテラルの `true`/`false` に固有です。

参考:
- PHP 8.0 named arguments RFC (Popov)
- PHP Internals News Ep. 59 — Popov が `true, true, false` を canonical な
  ケースとしてフレームしている
- Larry Garfield, "PHP 8.0 named arguments" — named は struct-object
  construction に *targeted* なツール
- PHPMD `BooleanArgumentFlag` — `bool` parameter は SRP smell
- PHPStan / Psalm / PHP-CS-Fixer の `@no-named-arguments` — ライブラリ境界の
  BC 用、内部スタイル用ではない

### Resource 内部のメソッド順序
1. `__construct`
2. public な `on*` ハンドラ。HTTP 動詞順 (`onGet`、`onPost`、`onPut`、`onDelete`)
3. `private` ヘルパ。すべての public メソッドの後

上から下に読むと、まず public な surface、最後に実装ディテールが来るように
します。ハンドラより上にヘルパがあると、読み手は entry point に到達する前に
内部 plumbing をスキップする必要が出てきます。

## 5. Read/Write SQL contract

- `SELECT` のカラム順は entity の `__construct` の positional 引数順と
  **必ず** 一致させます (Ray.MediaQuery の `FetchNewInstance` 経由の
  PDO::FETCH_FUNC contract)。カラムを足すときは両方のファイルを lock-step で
  更新します。
- `DbQueryInterceptor` は、すべての `#[DbQuery]` 呼び出しを return type に
  基づいて `getRow` / `getRowList` 経由でルーティングします。Write (`void`
  返却) も同じパスを通ります — `exec()` を直接呼ぶのは **やめてください**。

## 6. ファイル/データ配置

| 種類 | 場所 |
|------|------|
| Read/Write SQL | `var/db/sql/<entity>_<verb>.sql` |
| Doctrine migration | `var/db/migrations/Version<timestamp>.php` |
| Response JSON Schema | `var/json_schema/<entity>.json` (フラット、サブディレクトリなし) |
| Input JSON Schema | `var/json_validate/<entity>_<verb>.json` |
| Fake data | `var/fake/<entity>.json` (deterministic, `mt_srand(42)`) |
| ALPS profile | `var/alps/profile.json` (semantics の single source of truth) |
| 生成された apidoc | `docs/index.html`、`docs/openapi.json`、`docs/llms.txt`、`docs/schemas/*` |

## 7. テスト

- ユニットテスト (DB なし): default の suite。`vendor/bin/phpunit` で実行されます。
- 統合テスト (real DB): MySQL に対して走らせます (SQLite ではありません)。
  MySQL に到達できなければ自動 skip されます。
- mock は使いません。外部サービスは Docker を、内部依存は `tests/Fake/` 配下の
  Fake クラスを使います。

## 8. プロセス

| What | Convention |
|------|-----------|
| Commit メッセージ | 英語。trivial でない変更は multi-paragraph も可 |
| Commit 粒度 | 論理的な phase 1 つにつき 1 commit |
| 失敗系 / 試行錯誤の commit | 履歴として残し、squash しない |
| `Co-Authored-By` 行 | 使わない (Claude のみによる貢献は必要に応じて commit メッセージ本文で言及) |
| `CLAUDE.md` をリポジトリに置く | 置く — 将来の AI セッションへのプロジェクト固有の gotcha |
| main (`1.x`) からの分岐 | 必ず feature branch を作る。`1.x` に直接 commit しない |

---

## See also

- [architecture.md](architecture.md) — BDR pattern、contexts、
  意図的に作らなかったものの一覧
- [alps.md](alps.md) — ALPS profile からコードへの流れ
- [resources.md](resources.md) — リソースごとの HAL response shape
- [../journal/decisions-to-consult.md](../journal/decisions-to-consult.md) —
  元の議論ログ。そこの「OK」結論が上に成文化されています
