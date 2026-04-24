# 相談すべき決定事項リスト

BEAR.Cms 構築中、私が独断で決めて先に進めたが本来は合意を取るべきだった項目。
作者からの判断が欲しいもの順に並べる。

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
| 1 | Vendor/Package 名 | `MyVendor/Cms` | `bearsunday/BEAR.Cms` などに改名? |
| 2 | ライセンス | `proprietary` (skeleton 既定) | MIT/Apache などに? |
| 3 | "BEAR.Sunday 参照実装" の自称 | README で宣言済み | 公式に reference として位置づける? それとも個人実験? |
| 4 | 公開先 | `/Users/akihito/git/BEAR.Cms` ローカルのみ | `bearsunday/` org に push する? |

---

## P1: BEAR.Sunday 流儀との適合性

| # | 項目 | 私の選択 | 確認したいこと |
|---|------|---------|---------------|
| 5 | PHP namespace レイアウト | `src/Entity`, `src/Query`, `src/Command`, `src/Fake` | Hpplus.Maquia は `src/Domain`, `src/DbQuery`。BEAR 流の正解は? |
| 6 | Context 命名 | `fake-hal-api-app`, `test-hal-api-app` | `fake-` prefix は BEAR の慣例に合っているか |
| 7 | Module 構成 | `FakeModule` (binding) + `TestModule` (FakeModule install) | 二段階に分けたが、1 つで十分か |
| 8 | Resource 配置 | `src/Resource/App/Index.php` を作った | スケルトンは `src/Resource/Page/Index.php` のみ。App/Index も作るのが BEAR 流? |

---

## P2: アーキテクチャパターン (コピーされる)

| # | 項目 | 私の選択 | 別案 / 確認したいこと |
|---|------|---------|---------------------|
| 9 | Entity 表現 | `final readonly class`, public プロパティ | named constructor / setter / 別形? |
| 10 | Entity Factory | 不使用 (FetchNewInstance に任せる) | Factory 採用すべきケースの基準は? |
| 11 | `_embedded` 構築 | `onGet` 内で手動配列構築 | `authorId` が DB fetch 後判明するため `#[Embed]` を使えなかった。canonical な扱いは? |
| 12 | INSERT 後の id 取得 | `getBy{naturalKey}` (slug/email/filename) で再 SELECT | lastInsertId / RETURNING / Service 層 / 別の canonical があるか |
| 13 | Pagination | `#[Pager]` 不使用、Resource 層で `{items, page, perPage, count}` 手組み | Pager + Pages を採用すべきか。Pages の Fake はどう書くべきか |
| 14 | Read/Write 分離 | 別 interface (`Query`/`Command`) に分割 | 1 つの interface でも良いか / もっと細かく分けるか |
| 15 | SELECT カラム順契約 | Entity コンストラクタ引数順に揃える暗黙ルール | Factory で逃げる方が安全? |
| 16 | Fake の配置 | `src/Fake/` (ランタイムでも使える) | テスト専用 (`tests/Fake/`) が BEAR 流か |

---

## P3: 命名規約 (コピーされる)

| # | 項目 | 私の選択 | 別案 |
|---|------|---------|------|
| 17 | Read interface 名 | `ArticleQueryInterface` | `ArticleRepositoryInterface` / `ArticleReader` |
| 18 | Write interface 名 | `ArticleCommandInterface` | `ArticleWriter` / `ArticleMutator` |
| 19 | SQL ファイル名 | `get_article.sql` / `list_articles.sql` / `create_article.sql` | 動詞 prefix。Hpplus は別系統 |
| 20 | Migration クラス名 | `Version20260425000001` (Doctrine 既定) | `_create_articles_table` 等の suffix 付き? |
| 21 | ALPS Ontology 命名 | `articleId` / `articleSlug` (entity prefix) | `id` / `slug` (no prefix) のほうが ALPS 慣例? |
| 22 | HAL `_links` rel 名 | `articles` / `author` / `category` (HAL 慣習) | `goArticleList` / `goAuthor` (ALPS transition と揃える) — 現在 mixed |
| 23 | `getBy{naturalKey}` メソッド名 | `getBySlug`, `getByEmail`, `getByFilename` | 命名規則として canonical か |

---

## P4: 黙ってスコープから落とした項目

reference として完成度を主張するなら、これらは「あえて省いた」と明示するか
実装する必要がある。

| # | 項目 | 状況 | 判断 |
|---|------|------|------|
| 24 | JSON Schema による Input validation | schema は `var/schema/` にある、使っていない | 実装する? deferred で明記? |
| 25 | `Articles` レスポンスの `totalCount` | schema に定義済み、実装で欠落 | 入れる |
| 26 | `ArticleTag` の onPost 同期 | Read で `_embedded.tags` を返すが、Create/Update でタグ指定不可 | 実装する? |
| 27 | `#[Cacheable]` / `#[Refresh]` / `#[Purge]` | 配線していない | reference として入れる? |
| 28 | 重複 slug の 409 Conflict | DB UniqueConstraintViolation 直で出る | 適切なエラー shape にする? |
| 29 | 認証・認可 | 一切なし | 別の reference に分ける / この reference に最小実装入れる |
| 30 | 実 DB integration test | Fake のみ。SQLite で動作確認したが phpunit には入れていない | 実 DB suite を `tests/Integration/` で別 testsuite として作る? |

---

## P5: ファイル配置 (私のオレオレ)

| # | 項目 | 現状 | 確認 |
|---|------|------|------|
| 31 | semantic-ex 生成スクリプト | `bin/semantic-ex/*.py` | `tools/`, `scripts/`, `dev/` のほうが BEAR 流? |
| 32 | Fake JSON 命名 | `var/fake/data-50.{entity}.json` | `50` 数値を残す/外す |
| 33 | `var/schema/*.json` の場所 | 直下 | サブディレクトリ要否 |
| 34 | SQL / Migration 配置 | `var/db/sql/`, `var/db/migrations/` | Hpplus 流。BEAR 公式推奨は? |
| 35 | reflection 系 docs | `docs/` 直下に build-log / verified / wishes / critique / skill 等が並列 | サブディレクトリで整理? 別 branch? |

---

## P6: 外部依存

| # | 項目 | 現状 | 確認 |
|---|------|------|------|
| 36 | Doctrine Migrations | 採用済み (元の指示) | 維持で OK か。Phinx 派の声が強くないか |
| 37 | Malt | 採用済み (元の指示) | BEAR エコシステムの推奨 local infra として位置づける? |
| 38 | `justinrainbow/json-schema` | 入れたが未使用 (P4-#24 のため予定) | 使わないなら削除 |
| 39 | `ray/input-query` | 入れたが未使用 (Write は名前付き引数で代替) | 使わないなら削除、使うなら #[Input] に書き換え |

---

## P7: リポジトリ運営

| # | 項目 | 現状 | 確認 |
|---|------|------|------|
| 40 | Commit message 言語 | 英語 (multi-paragraph) | 日本語が良い場面もあったか |
| 41 | Commit 粒度 | 1 phase = 1 commit | 維持で OK か |
| 42 | 失敗系 commit の扱い | 残している (誤評論 → セルフレビュー → 第二セルフレビュー → verified) | squash / 削除 / 残す |
| 43 | `Co-Authored-By` 行 | 全 commit に付与 | 残す? |
| 44 | `CLAUDE.md` をレポジトリに置く | 置いた | BEAR プロジェクトとして推奨パターンか |

---

## 優先処理順 (提案)

1. **P0 (1-4)** をまず確定 → これが決まらないと他の判断軸が定まらない
2. **P1 (5-8)** を確定 → BEAR 流に揃えるか、独自路線で行くかが決まる
3. **P4 (24-30)** の「あえて省いた」線引き → reference として何を含めるか確定
4. **P2-P3 (9-23)** をまとめて決定
5. **P5-P7 (31-44)** は cosmetic、最後でよい

各項目について「現状維持」「変更」「削除」「あとで」の 4 択で答えを返してもらえれば、
次の作業に進めます。
