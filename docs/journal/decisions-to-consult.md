# 相談すべき決定事項リスト

BEAR.Cms 構築中、私が独断で決めて先に進めたが本来は合意を取るべきだった項目。
作者からの判断が欲しいもの順に並べる。

> **Note:** ここで「OK」と確定した項目は [../conventions.md](../conventions.md)
> に集約済み。新規にコードを書くときは conventions.md を参照すること。
> このファイルは合意プロセスの履歴として残す。

凡例:
- **P0**: プロジェクトのアイデンティティに関わる。次に何かする前に合意必要
- **P1**: BEAR.Sunday 流儀との適合度。reference 実装として広まる前に確認したい
- **P2**: アーキテクチャパターン。コピーされる
- **P3**: 命名規約。コピーされる
- **P4**: スコープから黙って落とした項目。あえて省いたか/作るべきかの判断
- **P5**: ファイル配置の私のオレオレ
- **P6**: 外部依存
- **P7**: リポジトリ運営

---

## P0: プロジェクトのアイデンティティ

| # | 項目 | 現状 | 検討 |
|---|------|------|------|
| 1 | Vendor/Package 名 | `MyVendor/Cms` | そのまま |
| 2 | ライセンス | `proprietary` (skeleton 既定) | MIT |
| 3 | "BEAR.Sunday 参照実装" の自称 | README で宣言済み | README で宣言 |
| 4 | 公開先 | `/Users/akihito/git/BEAR.Cms` ローカルのみ | 後で |

---

## P1: BEAR.Sunday 流儀との適合性

| # | 項目 | 私の選択 | 確認したいこと |
|---|------|---------|---------------|
| 5 | PHP namespace レイアウト | `src/Entity`, `src/Query`, `src/Command`, `src/Fake` | OK |
| 6 | Context 命名 | `fake-hal-api-app`, `test-hal-api-app` | `fake-` prefix は BEAR の慣例に合っているか OK |
| 7 | Module 構成 | `FakeModule` (binding) + `TestModule` (FakeModule install) | 二段階必要, DBでテスト |
| 8 | Resource 配置 | `src/Resource/App/Index.php` を作った | App/Index なくてもOK |

---

## P2: アーキテクチャパターン (コピーされる)

| # | 項目 | 私の選択 | 別案 / 確認したいこと |
|---|------|---------|---------------------|
| 9 | Entity 表現 | `final readonly class`, public プロパティ | OK |
| 10 | Entity Factory | 不使用 (FetchNewInstance に任せる) | ほとんど |
| 11 | `_embedded` 構築 | `onGet` 内で手動配列構築 | `authorId` が DB fetch 後判明するため <= uri templateでできる |
| 12 | INSERT 後の id 取得 | `getBy{naturalKey}` (slug/email/filename) で再 SELECT | lastInsertId / RETURNING / Service 層 / 別の canonical があるか |
| 13 | Pagination | `#[Pager]` 不使用、Resource 層で `{items, page, perPage, count}` 手組み | Pager + Pages を採用すべき<br />Pages の Fake は今は諦め |
| 14 | Read/Write 分離 | 別 interface (`Query`/`Command`) に分割 | OK |
| 15 | SELECT カラム順契約 | Entity コンストラクタ引数順に揃える暗黙ルール | OK |
| 16 | Fake の配置 | `src/Fake/` (ランタイムでも使える) | テスト専用 (`tests/Fake/`) がいい |

---

## P3: 命名規約 (コピーされる)

| # | 項目 | 私の選択 | 別案 |
|---|------|---------|------|
| 17 | Read interface 名 | `ArticleQueryInterface` | OK |
| 18 | Write interface 名 | `ArticleCommandInterface` | OK                                                           |
| 19 | SQL ファイル名 | `get_article.sql` / `list_articles.sql` / `create_article.sql` | article_item.sql<br />article_list.sql<br />article_add.sql<br /><br />article_update.sql |
| 20 | Migration クラス名 | `Version20260425000001` (Doctrine 既定) | no idea |
| 21 | ALPS Ontology 命名 | `articleId` / `articleSlug` (entity prefix) | `id` / `slug` |
| 22 | HAL `_links` rel 名 | `articles` / `author` / `category` (HAL 慣習) | `goArticleList` / `goAuthor` (ALPS transition と揃える) |
| 23 | Query メソッド名 (PK / 自然キー / 集合) | 当初: `getById` / `getBySlug` / `getByEmail` / `getByFilename` / `findAll` | 解決: `item(int $id)` / `by<NaturalKey>(...)` / `list()` に統一。`item ↔ list` の語彙対が `Article ↔ Articles` リソース対に対応し、`item` (PK) と `by<NaturalKey>` (自然キー) で意味的役割の違いをメソッド形でエンコードする。Resource プロパティも `$<entity>` (Query) / `$<entity>Cmd` (Command) に統一。詳細は `docs/conventions.md` §3。|

## P4: 黙ってスコープから落とした項目

reference として完成度を主張するなら、これらは「あえて省いた」と明示するか
実装する必要がある。

| # | 項目 | 状況 | 判断 |
|---|------|------|------|
| 24 | JSON Schema による Input validation | schema は `var/schema/` にある、使っていない | 見送り |
| 25 | `Articles` レスポンスの `totalCount` | schema に定義済み、実装で欠落 | 必要な時だけ入れる、普段話 |
| 26 | `ArticleTag` の onPost 同期 | Read で `_embedded.tags` を返すが、Create/Update でタグ指定不可 | 実装 |
| 27 | `#[Cacheable]` / `#[Refresh]` / `#[Purge]` | 配線していない | あったほうがいい TTLのcacheableじゃなくてイベントで消去するCachableResponseの方 |
| 28 | 重複 slug の 409 Conflict | DB UniqueConstraintViolation 直で出る | OK |
| 29 | 認証・認可 | 一切なし | この reference に最小実装入れる / Google認証 |
| 30 | 実 DB integration test | Fake のみ。SQLite で動作確認したが phpunit には入れていない | SQLiteじゃなくてMySQL |

---

## P5: ファイル配置 (私のオレオレ)

| # | 項目 | 現状 | 確認 |
|---|------|------|------|
| 31 | semantic-ex 生成スクリプト | `bin/semantic-ex/*.py` | そもそもphpに |
| 32 | Fake JSON 命名 | `var/fake/data-50.{entity}.json` | `50` 数値を外す data-50冗長 |
| 33 | `var/schema/*.json` の場所 | 直下 | OK |
| 34 | SQL / Migration 配置 | `var/db/sql/`, `var/db/migrations/` | OK |
| 35 | reflection 系 docs | `docs/` 直下に build-log / verified / wishes / critique / skill 等が並列 | サブディレクトリで整理 |

---

## P6: 外部依存

| # | 項目 | 現状 | 確認 |
|---|------|------|------|
| 36 | Doctrine Migrations | 採用済み (元の指示) | OK |
| 37 | Malt | 採用済み (元の指示) | OK |
| 38 | `justinrainbow/json-schema` | 入れたが未使用 (P4-#24 のため予定) | バリデーションして確かめて |
| 39 | `ray/input-query` | Article + Auth で `#[Input]` DTO 採用済み (`src/Input/`)。Author / Tag / Category / Media は scalar + `#[JsonSchema(params:)]` のままで対比表示。 | 残課題: #45 |

---

## P7: リポジトリ運営

| # | 項目 | 現状 | 確認 |
|---|------|------|------|
| 40 | Commit message 言語 | 英語 (multi-paragraph) | OK |
| 41 | Commit 粒度 | 1 phase = 1 commit | OK |
| 42 | 失敗系 commit の扱い | 残している (誤評論 → セルフレビュー → 第二セルフレビュー → verified) | OK |
| 43 | `Co-Authored-By` 行 | 全 commit に付与 | 不要　claudeだけでOK |
| 44 | `CLAUDE.md` をレポジトリに置く | 置いた | OK |

---

## P8: 後追いで埋めたい穴

| # | 項目 | 現状 | 判断 |
|---|------|------|------|
| 45 | ~~`#[JsonSchema(params:)]` で Input DTO を validate できない~~ **resolved (2026-04-29)** | 経緯メモ — `Article::onPost` / `Article::onPut` / `Auth::onPost` を `#[Input] <Dto>` 化したところ、当時の `JsonSchemaInterceptor` は flat scalar 引数前提で DTO を見ず、`OpenApiGenerator` も `#[JsonSchema]` 不在で early return するため openapi に requestBody が出ない、という上流ギャップが 2 件並んでいた。Codex によるベンダソース読みで `BEAR\Resource\InputParam` が呼び出し前に DTO を materialize → `getNamedArguments()` が `['input' => FooInput]` を見に行く、という挙動を特定。<br /><br />**着地 (2026-04-29):** 両ギャップが上流で塞がった。(a) [BEAR.Resource 1.31.1](https://github.com/bearsunday/BEAR.Resource/releases/tag/1.31.1) で [#356](https://github.com/bearsunday/BEAR.Resource/issues/356) (DTO 認識) を解消。(b) [BEAR.ApiDoc 1.9.1](https://github.com/bearsunday/BEAR.ApiDoc/releases/tag/1.9.1) (retag) で [#81](https://github.com/bearsunday/BEAR.ApiDoc/issues/81) を解消、同時に [PR #82](https://github.com/bearsunday/BEAR.ApiDoc/pull/82) で `phpdocumentor/reflection-docblock: ^5.2 \|\| ^6.0` に緩和して --prefer-lowest を維持。<br /><br />**MyVendor.Cms 側:** `composer update -W` で bear/resource 1.31.1 + bear/api-doc 1.9.1 に追従し、3 メソッドに `#[JsonSchema(schema: 'write_response.json'\|'auth_response.json', params: '<input>.json')]` を再付与。Auth は subject id が string (OAuth provider id) なので response schema を専用 `auth_response.json` に分離。`tests/Resource/App/ArticleTest.php` の deferred test を `testPostInputShapeValidationRejectsBadFields` に書き換え、validation が実際に効くことを assertion で固定。 | クローズ。Article/Auth の gap 説明 docblock も削除済み。 |

---

## 優先処理順 (提案)

1. **P0 (1-4)** をまず確定 → これが決まらないと他の判断軸が定まらない
2. **P1 (5-8)** を確定 → BEAR 流に揃えるか、独自路線で行くかが決まる
3. **P4 (24-30)** の「あえて省いた」線引き → reference として何を含めるか確定
4. **P2-P3 (9-23)** をまとめて決定
5. **P5-P7 (31-44)** は cosmetic、最後でよい

各項目について「現状維持」「変更」「削除」「あとで」の 4 択で答えを返してもらえれば、
次の作業に進めます。
