# リファレンス実装の特徴 — 深掘り分析

このドキュメントは MyVendor.Cms を「BEAR.Sunday の動くサンプル」ではなく
**production-shaped reference**（実務形をしたまま教材になっているコード）として
読み解くための分析です。重要度順にまず全体像を、続いて
**堅牢性 / AI コーディング親和性 / パフォーマンス** の三つのレンズで
見落としがちな観点を整理します。

`reading-guide.md` がコードを読む順序を、`highlights-for-veterans.md` が新旧 API の
差分を、`conventions.md` が運用規約を扱うのに対し、本書は **設計判断そのもの** を
俯瞰します。

---

## Part 1: 設計上の特徴（重要度順）

### 1. セマンティクス駆動のボトムアップ設計

ALPS profile (`var/alps/profile.json`) を **唯一の意味論的ソース** に据え、下流を
一方向に派生させる。

```
ALPS (Choreography 動詞 / Taxonomy 名詞)
  └→ Fake データ (var/fake/*.json, mt_srand(42) で決定論的)
       └→ JSON Schema (var/json_schema/*.json, 観測から生成)
            └→ #[DbQuery] / #[Embed] / #[Link] / #[JsonSchema] のコード
                 └→ DB マイグレーション + シード
                      └→ HAL+JSON / HTML / CLI の各表現
```

「コードから書き始めない」を README.md と AGENTS.md が読む順序として人間と
AI の双方に明示している点が、他の BEAR.Sunday サンプルと一線を画す。
JSON Schema は **規定** ではなく **観測** から生成されるため、実体と乖離しない。

### 2. FakeSqlQuery による完全ハーメチックテスト

`tests/Fake/FakeSqlQuery.php` は `SqlQueryInterface` の **本物の実装**（モックでは
ない）。`var/fake/*.json` の 50 件をメモリに載せ、Read/Write 全てを PHP で完結させる。

- DB / Docker / マイグレーション無しで `composer test` が即時実行される
- 同じ Resource コードが本物 PDO とフェイクで **同一レスポンス** を返す
- `execLog` を保持し、書き込み SQL の発行有無もテストで観測できる

「単体テストを書きやすくするために本体のロジックを薄くする」のではなく、
**ストレージ層を二系統用意する** という解法を取っている点が要。

### 3. 4 コンテキストの Module 合成

`src/Module/{App,Fake,Test,Html}Module.php` がインターフェイス1〜2本だけ差し替えて
性格を変える。

| Context | 用途 | 差し替え |
| --- | --- | --- |
| `hal-api-app` | 本番 HTTP | 本物 (AuraSqlModule + MediaQuery) |
| `cli-hal-api-app` | bin/app.php | 本物 |
| `fake-hal-api-app` | DB なし起動 | `SqlQueryInterface` → FakeSqlQuery |
| `test-hal-api-app` | PHPUnit | 上記 + `AuthInterface` → FakeAuthProvider |
| `html-hal-app` / `html-test-hal-app` | Qiq HTML | Resource 層は同じ、表現だけ追加 |

ビジネスコードに `if ($context === 'test')` が **一箇所も無い**。コンテキスト切替の
模範例として、規模の割に学べることが多い。

### 4. 属性駆動ハイパーメディア — HAL × ALPS の二層対応

`docs/conventions.md §3` の規約：

- `_links` の rel = ALPS **Choreography 動詞** (`goAuthor`, `doCreateArticle`)
- `_embedded` の rel = ALPS **Taxonomy 名詞** (`author`, `category`, `tagList`)
- `#[JsonSchema]` でレスポンス契約をピン留め

衝突回避と自己文書化を、**命名規則だけ** で達成している。HAL の構造に意味論を
重ねるという発想自体が稀。

### 5. Read / Write 分離 + `#[DbQuery]` + 命名規則

`src/Query/` に `*QueryInterface`（読み）と `*CommandInterface`（書き）を同一
ディレクトリに置き、`MediaQuerySqlModule` が一回スキャンで両方を拾う。
メソッド名と SQL ファイル名が完全一致：

| メソッド | 用途 | SQL ファイル |
| --- | --- | --- |
| `item(int $id)` | PK 検索 | `article_item.sql` |
| `bySlug(string $slug)` | 自然キー検索（INSERT 後の id 復元） | `article_by_slug.sql` |
| `list(...)` / `listByArticle(int $articleId)` | 複数行 | `article_list.sql` |
| `add` / `update` / `delete` / `clear` / `link` | 書き込み | `article_add.sql` 他 |

属性・メソッド名・SQL ファイル名が **同じ語彙** を共有するため、
ズレが構造的に発生しえない。

### 6. ハイパーメディア・ナラティブテスト

`tests/Hypermedia/EditorManagesArticleTest.php` などは `#[Depends]` で物語を連鎖：

```
Editor Manages Article
 ✔ Creates an article
 ✔ Reads back the new article
 ✔ Revises the article
 ✔ Retires the article
```

入口 URI だけを固定し、以降は `_links` の rel をたどって遷移する。ALPS の
動詞をリネームすると **このテストが最初に壊れる** よう設計されている。
Production 配線の前に semantic 層の破綻を検出できる、最も上位の安全網。

### 7. Variation Resources — 教材としての対比

`src/Resource/App/Variations/` に同じ Article GET の 3 実装：

| Variation | 比較軸 | 教材的問い |
| --- | --- | --- |
| `ArticleAsArray` | Entity vs 配列 | Entity は何を運んでいるのか |
| `ArticleSqlQuery` | `#[DbQuery]` vs 手続き的協調 | 単一クエリで足りないとき何が変わるか |
| `ArticleRawPdo` | MediaQuery vs 生 PDO | フレームワークが何を抽象しているか |

レスポンス形を意図的に揃えることで **Resource 層の差異だけが浮かび上がる**。
AGENTS.md は明示的に「これ以上 variation を増やすな」と釘を刺している
（教材としての完成度を保つため）。

### 8. 決定論的コード生成パイプライン

| `composer` タスク | 出力 | 決定性 |
| --- | --- | --- |
| `composer fake` | `var/fake/*.json` (50 件 × 各エンティティ) | `mt_srand(42)` |
| `composer schema` | `var/json_schema/*.json` | fake から観測 |
| `composer semantic` | 上記両方 | 同上 |
| `composer doc` | `docs/openapi.json`, `docs/alps.html`, `docs/llms.txt` | 属性から派生 |
| `composer cli` | `bin/cli/*` | `#[Cli]` から生成 |

差分レビューが意味を持つ。生成物が壊れたら属性側を直す。

### 9. Entity = Factory パターン（FETCH_FUNC 契約）

`final readonly` Entity のコンストラクタが行ファクトリを兼ねる
（`PDO::FETCH_FUNC` を使用）。

- SELECT のカラム順 = `__construct` の引数順、というハード制約
- Enum 復元・RFC3339 化など domain 知識は `src/Factory/` に集約
- SQL は純粋に保ち、コンストラクタが受け取る形に整える

CLAUDE.md と AGENTS.md の双方に「ここが地雷」と明記されている、移植時に
最も間違いやすいポイント。

### 10. `bySlug` リカバリで `void` Command を貫く

書き込みは全て `void` を返し、新しい id が必要なら **クライアントが渡した
自然キー** で再 SELECT する：

```php
$this->articleCmd->add($slug, $title, ...);    // void
$created = $this->article->bySlug($input->slug);
assert($created !== null);
```

- ポータブル（SQLite/MySQL/Postgres を跨いで動く）
- フェイク可能（`lastInsertId()` の DB 依存を排除）
- `#[DbQuery]` が「書きっぱなしで返り値を作らない」原則を貫ける

---

## Part 2: 堅牢性（Robustness）の観点

### 静的解析の強度

| ツール | 設定 | 効果 |
| --- | --- | --- |
| PHPStan | level 6 (`phpstan.neon`) | 型・null 性の厳格チェック |
| Psalm | error level 2 (`psalm.xml`) + `runTaintAnalysis="true"` | **テイント解析で injection 検出**（特に Page resource 経由の HTML 出力） |
| PHPCS | BEAR.Sunday 標準 + Doctrine + `--parallel=80` | 命名・構造の一貫性 |
| PHPMD | ShortVariable 以外を全て有効 | コード臭の検出 |

Psalm のテイント解析がオンになっている点は見落としやすい — Page resource を
追加するとき、`htmlspecialchars` を抜けた変数を `echo` すると CI で落ちる。

### 不変性と型の使い方

- 全 Entity が `final readonly`（`Article`, `Author`, `Category`, `Media`, `Tag`）
- ステータスは **typed enum**（`ArticleStatus = 'draft' | 'published'`）で文字列を排除
- Command は **`void` を返す**（id 取得経路を `bySlug` 等の自然キーに強制）
- Input DTO のコンストラクタが境界正規化を担い、`mixed` で受けて
  `is_array()` で判定 → `ParameterException` で 400 化（`TypeError` を 5xx に
  漏らさない）

### バリデーションの二段構え

1. **Input DTO** が PHP レベルの型と業務制約を担保（`src/Input/ArticleCreateInput.php`）
2. **JSON Schema**（`#[JsonSchema(params: 'article_create.json')]`）が属性レベルの
   形を担保

スカラ引数で十分な小さい操作（`Author::onPost`, `Tag::onPost`）と、DTO が必要な
複合操作（`Article::onPost` の 9 フィールド + `tagIds` 配列）の **使い分け** が
規約として codify されている。一律ルールではなく fit-driven。

### エラーハンドリングの規律

- 業務例外は最小限（`UnexpectedAuthProviderResponseException` 1 件のみ）
- Resource は throw せず **HTTP status を `$this->code` にセット** する流儀
- 404 のときも `body` を返してハイパーメディア性を維持

### テスト層の三段構成

1. **Resource 単体テスト** — `tests/Resource/App/*Test.php`、FakeSqlQuery 上で動作
2. **Hypermedia narrative テスト** — `tests/Hypermedia/*Test.php`、入口 URI のみ固定
3. **Integration テスト** — 実 MySQL、利用不可なら自動 skip

「テストを書くために本体を曲げない」という原則が、層構造で保証されている。

---

## Part 3: AI コーディング親和性

このリファレンス実装の **隠れた主目的** はここにあると言ってよい。BEAR.Sunday は
属性ベースで宣言性が高く、AI コーディングと相性が良いが、それを最大限に引き出す
書き方が codify されている。

### `AGENTS.md` と `CLAUDE.md` の二段構え

| ファイル | 想定読者 | 内容 |
| --- | --- | --- |
| `AGENTS.md` | 任意の AI agent / 人間の新規参加者 | namespace、context 一覧、coding rules、Variations を増やすな等の禁止 |
| `CLAUDE.md` | Claude Code セッション特化 | 三大地雷（DbQuery dispatch / FETCH_FUNC 列順 / 自然キー id 復元）、即実行できるコマンド集 |

両者を分けることで「全 AI 向けの永続規約」と「セッション運用 tips」を区別している。

### `docs/llms.txt` — フラット化された API 表面

`composer doc` が ALPS / 属性 / SQL から **AI 巡回用に flatten した** テキストを
1 ファイル生成する。ルート、Resource、レスポンス形、SQL クエリが一読で取れる。
これがあるおかげで AI は「存在しないエンドポイントを発明する」ハルシネーションを
避けやすい。

### Single Source of Truth による drift 防止

| 真実の源 | 派生先 | drift の起き場所 |
| --- | --- | --- |
| `var/alps/profile.json` | rel 命名、ナビゲーション | 属性で参照する rel 名と一致 |
| `#[DbQuery('article_item')]` | `var/db/sql/article_item.sql` | ファイル名が属性と一致 |
| Entity `__construct` 引数順 | SQL の SELECT 列順 | FETCH_FUNC で実行時検証 |
| `#[Cli]` 属性 | `bin/cli/*` | `composer cli` で再生成 |

**「同じ語彙が複数の場所で使われ、再生成すれば一致する」** ため、AI が局所的な
変更を行っても全体の整合性が壊れにくい。

### 「再生成して検証」ループ

```
コード変更 → composer semantic → 差分が想定通りか目視 → composer tests
```

`mt_srand(42)` で fake が決定論的 / JSON Schema が観測由来 / OpenAPI が属性派生。
AI の出力を信頼するのではなく **生成物の差分** を信頼する設計。

### 推論コストを下げる命名

- メソッド名から SQL ファイル名と引数の意味が推測できる（`bySlug` → `_by_slug.sql`、引数は slug）
- Resource URI から Resource クラスが推測できる（`app://self/article` → `src/Resource/App/Article.php`）
- ALPS の動詞から HAL の rel が推測できる（`goAuthor` → `_links.goAuthor`）

AI は探索コストが品質に直結する。**ファイルの場所を当てる作業がほぼ不要** な
ディレクトリ規約は、それ自体がアーキテクチャ。

### Variations は AI に対する教材

`src/Resource/App/Variations/README.md` は人間にも AI にも「同じ問題に対する
3 つの解の比較」を提示する。AI が Resource の書き方を悩んだとき、ここを見れば
**比較対象が用意されている**。

---

## Part 4: パフォーマンスの観点

### DI コンパイルとキャッシュ

- 本番は `prod-hal-app` コンテキストでバインディングをコンパイル済みコンテナに
  焼き込む（`composer compile` → `var/tmp/*-hal-app`）
- module バインディングを変えたら **全コンテキストの DI キャッシュを消す** 必要が
  ある（CLAUDE.md / AGENTS.md §2）

### #[Embed] の遅延性

`#[Embed]` は **URI スロットを予約するだけ** で、デフォルトでは fetch しない。
`$this->body['author']->addQuery(['id' => $article->authorId])` で query を
継ぎ足し、最終シリアライズ時にまとめて解決される設計。N+1 はクライアント側の
fetch 戦略 / `?embed=` パラメータによって制御できる。

### `+= 結合` で `#[Embed]` スロットを潰さない

```php
$this->body['author']->addQuery(['id' => $article->authorId]);
$this->body += [          // ← 連想代入ではなく union
    'id' => $article->id,
    'slug' => $article->slug,
];
```

`$this->body['k'] = $v;` を順次代入すると Embed スロットを上書きしてしまう。
`+=` は **既存キーを保護する** ので Embed が消えない。これは規約というより
**演算子の意味を活かしたバグ予防** で、コードレビューの目利きポイント。

### MediaQuery のディスパッチ

`DbQueryInterceptor` は `#[DbQuery]` の **戻り値型** から `getRow` / `getRowList` を
振り分ける（`exec()` は使わない）。FakeSqlQuery 側もこの規約に乗って分岐するため、
本物とフェイクの実行経路が同じ shape を保つ。

### Variation で「速度の差」を見える化

| Variation | 特徴 | コスト |
| --- | --- | --- |
| 正規 `Article` | `#[DbQuery]` 単発 + `#[Embed]` で必要時のみ展開 | 最小 |
| `ArticleSqlQuery` | 前後ナビ等で複数 query を programmatic に発行 | クエリ数増 |
| `ArticleRawPdo` | 生 PDO で fetch、binding も自前 | クエリ数 + 手作業のミスコスト |

教材として「何が速いか」ではなく **「何が抽象されているか」** を見せている。

### 生成物のキャッシュ性

- `composer compile` で本番 DI を warm up
- ApiDoc / OpenAPI / ALPS HTML は CI で生成して静的配信できる
- llms.txt も静的成果物なので AI セッションが任意のタイミングで読める

---

## Part 5: 見落としやすい補助要素

### 環境設定とマイグレーション

- `env.schema.json` で env を JSON Schema 検証（Koriym.EnvJson）
- `migrations.php` + `migrations-db.php` で Doctrine Migrations
- `bin/seed.php` で実 DB 用の seed（fake と同じ前提で動く）

### Quality Gate

`composer tests` は単発テストではなく **ゲートのバンドル**：

- `phpunit`
- `phpcs` (parallel=80)
- `phpstan analyse` (level 6)
- `psalm` (taint analysis 有効)
- `phpmd`

`composer doc` / `composer semantic` / `composer cli` は **再生成タスク**。
両者を分けているため CI が速い。

### CI / GitHub Actions

6 つのワークフロー：`continuous-integration` / `static-analysis` / `security` /
`alps` / `apidoc` / `coding-standards`。重い静的解析は `workflow_dispatch` で
明示起動するなど、毎 push の負荷を抑える分担。

### ローカル環境

- `malt.json` … Malt（PHP 8.5 + MySQL 8.0 + Nginx + xdebug/pcov/pdo_mysql）
- `docker-compose.yml` … Docker 派の選択肢
- どちらも `composer fake-cli` 経由で **DB なしの動作確認** が可能

### Page / Qiq テンプレート

`src/Resource/Page/*` は Resource を embed して HTML 化。書き込み UI は無く、
**Page は HAL API の薄いビュー**。SSR のための独立した抽象を増やしていない点が
むしろ重要。

---

## まとめ

このリファレンス実装の本質は、

1. **意味論を最上位の真実とする一方向パイプライン**
2. **DB なしで全層が走るハーメチックなテスト基盤**
3. **属性 + 命名規則による drift 不可能なディレクトリ規約**
4. **生成物の決定性に支えられた「再生成して検証」ループ**

の 4 点が **同時に成立している** ことです。それぞれは個別に他の BEAR プロジェクトでも
見られますが、全部揃っているのは稀。結果として、約 800 行の業務コードに
BEAR.Sunday / Ray.MediaQuery / ALPS / HAL / Qiq の設計判断が **過不足なく** 詰まり、
**人間にも AI にも読みやすく / 壊しにくい** コードベースが成立しています。

このサイズ感を保つことそのものが設計判断であり、AGENTS.md の「Variations を
増やすな」「特定構造を増やすな」という禁止条項が、教材性を守るための
**意図的な抑制** として効いています。
