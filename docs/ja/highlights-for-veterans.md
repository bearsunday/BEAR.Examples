# 久しぶりに BEAR.Sunday を触る人のための見どころガイド

[English](../en/highlights-for-veterans.md)

このガイドは BEAR.Sunday を以前触ったことがあるが、最近のバージョン
（Resource 1.31+ / MediaQuery / InputQuery / Cli / ApiDoc 1.9+ など）を
追えていない読者向けの "差分ツアー" です。BEAR 101 ではなく **「以前はこう
書いていたものが、今はこう書ける」** だけに絞ります。

各節は

- **昔の書き方** — おそらく記憶にある形
- **今の書き方** — このリポジトリで採用されている形
- **見るファイル** — 実物への動線

の三段で並べます。順番は読むときに刺さりやすい順で、フレームワーク
依存度の高い項目を先に置いています。

---

## 1. アノテーション → PHP 8 Attribute に全面移行

**昔**: docblock の `@Embed`, `@Link`, `@JsonSchema`, `@DbQuery`。Doctrine
Annotations への依存と IDE 補完の弱さがあった。

**今**: PHP 8 ネイティブ `#[Attribute]`。docblock は型情報以外には
ほぼ書かない。`@SuppressWarnings` のような外部ツール向けのものだけ残る。

```php
#[Alps('goArticle')]
#[Link(rel: 'goArticleList', href: 'app://self/articles')]
#[Embed(rel: 'author', src: 'app://self/author')]
#[JsonSchema('article.json')]
#[Cli(name: 'article-show', description: 'Show an article by id', output: 'title')]
public function onGet(#[Option(shortName: 'i', description: 'Article id')] int $id): static
```

**見るファイル**: `src/Resource/App/Article.php:24-43`

---

## 2. 生 PDO / SqlInterface を書かない — `#[DbQuery]` で SQL 外出し

**昔**: Resource 内で `ExtendedPdoInterface` を直接受け取り、`$pdo->fetchOne()` を呼ぶ。
SQL は文字列リテラルでクラスに同居。

**今**: interface のメソッドに `#[DbQuery('article_item')]` を貼るだけ。
SQL は `var/db/sql/article_item.sql` に外出し。`DbQueryInterceptor` が
返り値の型から `getRow` / `getRowList` を自動で振り分ける。`exec()` は
基本的に書かない。

```php
interface ArticleQueryInterface
{
    #[DbQuery('article_item', factory: ArticleFactory::class)]
    public function item(int $id): Article|null;

    #[DbQuery('article_list'), Pager(perPage: 'perPage')]
    public function list(int|null $categoryId = null, /* ... */, int $perPage = 20): PagesInterface;
}
```

`factory:` で `Article` エンティティへ直接マッピングできるので、
`array → DTO` の手書き変換を消せる（ `PDO::FETCH_FUNC` ベース）。

**比較対象**: 同じ GET を生 PDO で書いた版が `src/Resource/App/Variations/ArticleRawPdo.php` に
わざと残してあります。MediaQuery が肩代わりしている量がそのまま見えます。

**見るファイル**: `src/Query/ArticleQueryInterface.php`、`var/db/sql/article_item.sql`

---

## 3. Read/Write を interface レベルで分離

**昔**: 1 つの `*Repository` に find / save / delete を全部生やしていた。

**今**: `<Entity>QueryInterface` (Read) と `<Entity>CommandInterface` (Write)
を **必ず** 別 interface に分ける。同じディレクトリ (`src/Query/`) に置きつつ、
suffix だけで責務を区別する。MediaQuery のスキャンが楽になる副次効果も
ある。

メソッド名規約も以下に揃っている:

| 種類 | 形 | 例 |
|---|---|---|
| PK 1 件 read | `item` | `item(int $id)` |
| 自然キー read | `by<NaturalKey>` | `bySlug`, `byEmail` |
| 複数行 read | `list` | `list()`, `listByArticle()` |
| Write | 命令動詞 | `add`, `update`, `delete`, `clear`, `link` |

**見るファイル**: `src/Query/ArticleQueryInterface.php`、`src/Query/ArticleCommandInterface.php`、
`docs/ja/conventions.md` §3

---

## 4. エンティティは `final readonly class` + `enum`

**昔**: 連想配列 `array{id: int, ...}` を resource 間で持ち回るか、
public mutable プロパティ + setter のクラスにしていた。

**今**: PHP 8.2 `readonly` で全プロパティ immutable、ステータスは `enum`。
`isPublished()` のようなドメイン判定は **エンティティ側のメソッド** に
置く（resource や view に散らさない）。

```php
final readonly class Article
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $title,
        public ArticleStatus $status,   // enum
        // ...
    ) {}

    public function isPublished(): bool
    {
        return $this->status === ArticleStatus::Published;
    }
}
```

`Variations/ArticleAsArray.php` には **わざと配列を返す版** が
残してあり、エンティティクラスを噛ませる手間が何の見返りを生むかを
比較できます。

**見るファイル**: `src/Entity/Article.php`、`src/Entity/ArticleStatus.php`、
`src/Resource/App/Variations/ArticleAsArray.php`

---

## 5. 入力 DTO は `Ray.InputQuery` + `#[Input]`

**昔**: `onPost(string $slug, string $title, string $body, ...)` のフラットな
シグネチャに 9 引数並べる、もしくは Resource 内で `$_POST` を手で組み替える。

**今**: `final readonly class ArticleCreateInput` を作り、
コンストラクタ引数に `#[Input]` を貼る。Resource 側は `#[Input]
ArticleCreateInput $input` で受けるだけ。BEAR.Resource が flat な request
配列から自動的にオブジェクト化する。**モジュール bind は不要**
(`ResourceClientModule` が標準で組み込んでいる)。

```php
public function onPost(#[Input] ArticleCreateInput $input): static
{
    $this->articleCmd->add(
        $input->slug, $input->title, $input->body,
        // ...
    );
}
```

DTO を使うか scalar のままにするかは **fit-driven** で、`docs/conventions.md` §4
の表が判定基準。「フィールド数が増えた」「tri-state がある」「struct として
意味を持つ」のいずれかを満たすときだけ DTO 化。

**見るファイル**: `src/Input/ArticleCreateInput.php`、`src/Resource/App/Article.php:74-105`

### 落とし穴 (Resource 1.31.1 / ApiDoc 1.9.1 時点)

`JsonSchemaInterceptor` の検証は **DTO hydration の後** に走るため、
typed array プロパティ (`public array $tagIds`) に scalar が来ると
コンストラクタで `TypeError` (5xx)。回避は DTO 側で `mixed` で受けて
`is_array` ガード → `ParameterException` (400)。`ArticleCreateInput::tagIds`
の構造はその回避パターンの実例。

---

## 6. `fake-*` context — DB なしで動くもう 1 つのアプリ

**昔**: 開発するときも MySQL 必須、CI も DB を立てる必要があった。
`prod-app-html` / `app` / `test-app` の三層が定番。

**今**: `fake-hal-api-app` という context が追加された。`SqlQueryInterface`
を `FakeSqlQuery` に差し替えるだけで **アプリのコード本体は同一のまま**
DB なしで動く。`composer demo` (`bin/demo.php`) で体験できる。

```bash
composer demo                # fake context — no DB needed
composer demo:variations     # 比較用 GET 3 つを並べて実行
```

`tests/Fake/` は `composer.json` の `autoload-dev` で読まれるので、
`composer install --no-dev` した本番アーティファクトには混入しない。

**見るファイル**: `src/Module/FakeModule.php`、`tests/Fake/FakeSqlQuery.php`、
`composer.json` の `autoload` / `autoload-dev`

---

## 7. Hypermedia workflow test — `#[Depends]` で rel を辿る

**昔**: 各 resource に対する isolated な test (`get('/article')` → 200) しか
書いていなかった。HAL の `_links` が壊れても気づけない。

**今**: `tests/Hypermedia/` に **ユーザーストーリー単位** で 1 ファイル。
クラス名がストーリー名、メソッド名がステップ名。PHPUnit の
`#[Depends]` で前ステップの `ResourceObject` を受け取り、
`AbstractWorkflowTestCase::follow($prev, $rel, $vars)` で
`#[Link]` を辿る。

```text
Editor Manages Article
 ✔ Creates an article
 ✔ Reads back the new article
 ✔ Revises the article
 ✔ Retires the article
```

**ストーリー内で URI のハードコードは入口の 1 箇所だけ**。それ以降は
`href($rel, ...)` で `#[Link]` 定義から URI Template を展開するので、
**rel を rename したらここで落ちる** 保証になる。ALPS の Choreography
レイヤを実コードで担保する仕組み。

**見るファイル**: `tests/Hypermedia/EditorManagesArticleTest.php`、
`tests/Hypermedia/AbstractWorkflowTestCase.php`、
`docs/ja/conventions.md` §7.1

---

## 8. ALPS profile と HAL を意識的に分けて書く

**昔**: rel 名は雰囲気で決めていた (`author` / `getAuthor` / `goAuthor` が
同じプロジェクトに混在しがち)。

**今**: ALPS の二層 — Choreography (動詞: `goArticleList`, `doCreateArticle`) と
Taxonomy (名詞: `author`, `category`, `tagList`) — を **HAL の
`_links` と `_embedded` に直接対応させる**。

| Where | ALPS layer | 例 |
|---|---|---|
| `#[Link]` rel | Choreography | `goArticleList`, `doCreateArticle` |
| `#[Embed]` rel | Taxonomy | `author`, `category`, `tagList` |

`#[Embed(rel: 'goAuthor', ...)]` のような混在は規約違反として
検出する文化になっている。`tests/Hypermedia/HalEnvelopeContractTest.php`
が contract pin として張られている。

**見るファイル**: `var/alps/profile.json`、`src/Resource/App/Article.php:34-40`

---

## 9. `#[Cli]` でリソースが CLI コマンドになる

**昔**: CLI と HTTP を別の入口として書いていた。共通化のため
独自の Application service を作るのが定番だった。

**今**: `BEAR.Cli` の `#[Cli]` を `on*` メソッドに貼ると、その
リソースメソッドがそのまま CLI として実行できる。`#[Option]` で
引数定義、`output:` でフィールド指定。

```php
#[Cli(name: 'article-show', description: 'Show an article by id', output: 'title')]
public function onGet(#[Option(shortName: 'i', description: 'Article id')] int $id): static
```

`bin/cli/article-show -i 1` のように呼べる。HTTP と CLI でロジックが
**同一クラスの同一メソッド** なので、副作用の二重実装が消える。

**見るファイル**: `src/Resource/App/Article.php:42`、`bin/cli/`

---

## 10. JSON Schema 入力検証が DTO まで貫通

**昔**: `#[JsonSchema(params: ...)]` は scalar 引数にしか効かず、DTO で
受けると検証がスキップされていた (BEAR.Resource 1.31.0 以前)。

**今**: BEAR.Resource [1.31.1](https://github.com/bearsunday/BEAR.Resource/releases/tag/1.31.1)
+ ApiDoc [1.9.1](https://github.com/bearsunday/BEAR.ApiDoc/releases/tag/1.9.1)
で `JsonSchemaInterceptor` が `#[Input]` DTO を unpack してから
`params:` schema で検証するようになった。`OpenApiGenerator` も
DTO ベースの `requestBody` schema を出す。

つまり `#[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]`
を貼れば、DTO のフィールド (slug regex / status enum / 長さ制限) が
**resource boundary で検証される**。Resource 内で手書きバリデーションを
書く必要はもうない。

**見るファイル**: `src/Resource/App/Article.php:73`、`var/json_validate/article_create.json`

---

## 11. 採用しなくなったもの (意図的に "やらない")

ここは記憶のアップデートが必要なポイント。`docs/conventions.md` で
**明示的に採用しない、または意図的に別解にしているもの** を抜粋:

- **Full pager UI rendering** — Article collection の DB access は
  `#[Pager]` / `PagesInterface` を使うが、HTML template は previous / next link
  を必要最小限に描画。
- **`lastInsertId`** — driver 依存 + fake 化が面倒なので、INSERT 後は
  `by<NaturalKey>` で再 SELECT。Command 側は `void` を返す。
- **Mock オブジェクト** — `tests/Fake/` の Fake class で代替。Mockery /
  Prophecy / PHPUnit Mock は使わない。
- **`Ray.MediaQuery` の `#[DbQuery]` への DTO bind** — 機能としては
  存在する (`ParamConverter::expandInputObjects`) が、Resource → Command
  境界で scalar に unpack することで `syncTags()` のような追加処理が
  hide されないことを優先。
- **規約としての "引数 N 個以上で named arguments"** — RFC の論拠
  (literal `true`/`false` / 中間 optional スキップ) に絞った2ケースだけ採用。

**見るファイル**: `docs/ja/conventions.md` の "採用しなかったもの" セクション

---

## 12. 学習路の推奨順

新しい書き方に慣れ直すなら以下の順で読むのが効率的:

1. `src/Resource/App/Article.php` — 全部入りリソース 1 本
2. `src/Query/ArticleQueryInterface.php` + `var/db/sql/article_item.sql` — `#[DbQuery]` の動線
3. `src/Resource/App/Variations/ArticleRawPdo.php` — MediaQuery を **使わない** とどうなるか
4. `src/Input/ArticleCreateInput.php` — DTO + `#[Input]` の境界処理
5. `tests/Hypermedia/EditorManagesArticleTest.php` — workflow test 1 本
6. `tests/Fake/FakeSqlQuery.php` — fake context が動く仕組み
7. `docs/ja/conventions.md` — 規約全体 (なぜそうしたか含む)

---

## 関連ドキュメント

- [reading-guide.md](reading-guide.md) — 初見からの段階的コードリーディング
- [architecture.md](architecture.md) — BDR パターン、context 構成
- [conventions.md](conventions.md) — 全規約と採否の判断
- [alps.md](alps.md) — ALPS profile から code への流し込み
