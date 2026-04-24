# 構築ログ

BEAR.Cms を ALPS → Fake → 実SQL の順に解像度を上げながら構築した記録。
各フェーズの作業内容、判断、躓いたポイントを残す。

## 前提

- プロジェクトディレクトリ: `/Users/akihito/git/BEAR.Cms`
- VENDOR/PACKAGE: `MyVendor/Cms`
- 開発DB: MySQL 8 (malt前提)、CI・試用は SQLite でも可
- スコープ: App リソースのみ。Admin / HTML / Twig / JS なし
- 構築期間: 2026-04-25 (1セッション)

## フェーズ別作業

### Phase 1: スケルトン生成

`composer create-project bear/skeleton` でMyVendor/Cms を生成。
既存の `.claude/settings.local.json` を保持するため一度 `/tmp/bear-cms-skel` に
生成してから `cp -R ./.` で中身を移動。

追加パッケージ:
- `ray/media-query` — `#[DbQuery]` によるSQL分離
- `ray/aura-sql-module` — PDO接続
- `ray/input-query` — Write用 (将来拡張枠で追加)
- `bear/query-repository` — `#[Cacheable]` 枠
- `doctrine/migrations` + `doctrine/dbal` — マイグレーション (Phinxは使わず)
- `justinrainbow/json-schema` — JSON Schema検証用

`git init` + 初期commit。

### Phase 2: ALPS プロファイル設計

`alps-skills:alps` スキルを使い、自然言語から ALPS プロファイルを生成。

- Ontology: 26 ディスクリプタ (article*/category*/tag*/author*/media* + pagination)
- Taxonomy: 9 状態 (Index含む)
- Choreography: Read (`go*`) 8件 + Write (`do*`) 12件

**躓き:** 初版で `"tag": ["a", "b"]` と配列で書いたら E011 エラー。ALPS仕様では
スペース区切り文字列 `"tag": "a b"` が正。Python ワンライナーで全件変換。

`asd --validate` の結果: 0 errors / 0 warnings / 17 suggestions (全て
"Consider adding doc to transition" — 非ブロッキングなので受容)。

### Phase 3: semantic-ex (Fake生成 + JSON Schema発見)

`be-framework-skills:semantic-ex` の3フェーズを適用:
- Phase 1 (Experience): 50件/エンティティのリアル Fake 生成
- Phase 2 (Examples): 最長/最短/null率を観察 (`var/fake/observations.md`)
- Phase 3 (Constraints): 観察値から maxLength 等を導出 → JSON Schema

250件 (5エンティティ×50) の Fake を手書きは現実的でないため、
`bin/semantic-ex/gen-fake.py` を書いて `random.seed(42)` で再現可能に。
参照整合性を保証: article.authorId / categoryId は必ず存在するID。

**躓き 1:** `publishedAt` を `"2026-01-01 10:07:00"` 形式で生成したが JSON Schema
の `format: date-time` は RFC3339 を要求。`"2026-01-01T10:07:00Z"` に修正。

**躓き 2:** 日本語名の著者から email ベースを生成したら非ASCIIが混入して email
format 検証 fail。ASCII-only に正規化。タグの `日本語` slug も `japanese` に変更。

最終: 5エンティティ全50件が自分のschemaで validate pass。

### Phase 4: BDR Read (Entity + Query Interface + FakeSqlQuery)

- `src/Entity/*` — `final readonly class` ×5
- `src/Query/*` — `#[DbQuery(id, type: 'row'|'row_list')]` ×5
- `src/Fake/FakeSqlQuery.php` — `SqlQueryInterface` 実装

**設計判断:** Factory クラスは使わず `FetchNewInstance` (PDO::FETCH_FUNC) に任せる。
これで SELECT 列順を entity コンストラクタ引数順に揃えるだけで変換される。
ただし FakeSqlQuery は `$fetch` を無視して entity を直接構築 (JSONの形と
entity がほぼ一致しているため)。

**設計判断:** FakeSqlQuery を当初 `tests/Fake/` に置いたが、`fake-hal-api-app`
コンテキストでランタイム利用したいため `src/Fake/` に `git mv`。
autoload-dev だけでは prod/dev 両用にならない問題を解消。

**設計判断:** `#[Pager]` は使わず `list()` がプレーン配列を返す設計に。
Pager は `PagesInterface` (Pagerfanta + PDO依存) を返すため Fake化コストが高い。
Resource 層で `page/perPage/count` を組み立てる。

### Phase 5: Read Resource

9 App リソース (`Index`, `Article`, `Articles`, `Category`, `Categories`,
`Tag`, `Tags`, `Author`, `Media`)。

**設計判断:** Author/Category/Tags を `_embedded` に入れるが `#[Embed]` 属性は
使わない。`#[Embed]` の src URI テンプレートは request 引数から展開されるが、
article の authorId/categoryId は DB fetch 後でないと分からないため。
Resource 内で明示的に `$this->body['_embedded']` を組み立てる。

### Phase 6: インフラ (malt + Module/DI)

`malt.json` — PHP 8.4 + MySQL 8 + Nginx + xdebug/pcov/pdo_mysql。

AppModule:
- `AuraSqlModule($dsn, $user, $pass)` — env 経由
- `MediaQueryBaseModule` + `MediaQueryDbModule` で Query + Command 両方のディレクトリを
  スキャン (`MediaQuerySqlModule` は単一dirなのでバイパス)

FakeModule → SqlQueryInterface を FakeSqlQuery に差し替え。
TestModule → FakeModule を install するだけ。

コンテキスト:
| prefix | 用途 |
|--------|------|
| (なし) `hal-api-app` | 実DB本番 |
| `fake-` | 実DBなし、FakeSqlQuery |
| `test-` | PHPUnit |

**検証:** `fake-hal-api-app` コンテキストで `app://self/article?id=1` が HAL+JSON
を返すことを確認。`_embedded.author`, `_embedded.category`, `_embedded.tags` まで
正しく埋め込まれた。

### Phase 7: Doctrine Migrations + seed

6 migrationクラス: authors → categories (自己参照FK) → tags → articles
(authors/categories への FK) → article_tags (join) → media。

`bin/seed.php` — `var/fake/*.json` を INSERT。Ray.MediaQuery Fake と同じ
データセットを実DB にロード: Fake/real で挙動を比較する土台になる。

Doctrine migrations の DB 設定 (`migrations-db.php`) は `.env` の PDO DSN を
パースして DBAL params に変換。

**検証:** SQLite (`/tmp/bear_cms_test.db`) で
`doctrine-migrations migrate` → `php bin/seed.php` が成功、6テーブル + 365行。
MySQL は malt 起動前提のため SQLite で代行確認。

### Phase 8: Fake → 実SQL差し替え

`var/db/sql/*.sql` を書く。DB 列名は snake_case、entity は camelCase。
`FetchNewInstance` は位置引数でコンストラクタを呼ぶため **列の名前ではなく順序** が
重要。エンティティコンストラクタ引数順に揃えた SELECT にする。

SQL 9本 (Read):
- `get_article`, `list_articles`, `get_category`, `list_categories`,
- `get_tag`, `list_tags`, `list_tags_by_article`, `get_author`, `get_media`

**検証:** 同じ SQLite DB に対し `hal-api-app` コンテキストで
`app://self/article?id=1` を呼び、Fake と同じ body shape を返すことを確認。
違いは `publishedAt` のフォーマット (DBは `YYYY-MM-DD HH:MM:SS`、Fakeは RFC3339) と
NULL の扱い (SQLiteは null、Fakeは `""` の場合あり) のみ。想定内。

### Phase 9: Write

**一番の設計判断ポイント:** `onPost` でどう新ID を返すか。
候補:
1. `ExtendedPdoInterface::lastInsertId()` を Resource に inject → driver 依存 + Fake困難
2. INSERT ... RETURNING → SQLite/Postgres のみ、MySQL不可
3. INSERT 後に `SELECT MAX(id)` → race condition
4. **INSERT 後に natural unique key で `getBy*` SELECT** ← 採用

採用理由: ポータブル (全DB対応)、Fake化簡単、Command interface を `void` 戻り型の
ままキープできる。slug/email/filename は UNIQUE 制約で保護済み。

- `src/Command/*` — `#[DbQuery]` Write interface ×5
- `src/Query/*` に `getBySlug` / `getByEmail` / `getByFilename` を追加
- 各 Resource に `onPost` / `onPut` / `onDelete` を追加
- `var/db/sql/` に create/update/delete と getBy* のSQLを追加

**躓き (重要な発見):** Command を実行しようとしたら
`FakeSqlQuery: unknown row sqlId 'create_article'` エラー。

原因: `DbQueryInterceptor::invoke()` は **戻り型と `type` 属性だけで分岐** し、
常に `getRow` か `getRowList` を呼ぶ。`SqlQueryInterface::exec()` は DI 経由の
`#[DbQuery]` では **一度も呼ばれない**。

実 `SqlQuery` は内部の `perform()` で SQL 文字列を見て "SELECT で始まらなければ
結果を `[]` で返す" という実装になっており、getRow 経由でも INSERT/UPDATE/DELETE が
普通に走る。Fake も同じ契約 (writes は getRow/getRowList 内で処理) に合わせる必要が
ある。

→ FakeSqlQuery に `mutate()` private helper を作り、`getRow`/`getRowList`/`exec`
すべてから呼ぶように変更。これが文字通り Ray.MediaQuery の挙動と一致する。

**検証:** Fake と 実SQLite の両方で POST → GET → PUT → GET (title変化) →
DELETE → GET (404) のラウンドトリップが通った。

### Phase 10: テスト

`tests/AbstractAppTestCase.php` — `test-hal-api-app` コンテキストで
`ResourceInterface` を受け取る共通setUp。

7テストファイル (Index/Article/Articles/Category/Tag/Author/Media)、
19 tests / 63 assertions、すべて Fake 経由で DB不要。

ArticleTest は POST→GET→PUT→GET→DELETE→GET の完全ラウンドトリップをカバー。

### Phase 11: ドキュメント

- `README.md` — セットアップ (Fake / Malt+MySQL / SQLite) + 4コンテキスト表 + URI一覧
- `docs/architecture.md` — 解像度上昇フロー、BDRレイヤ、interceptor dispatch の
  注意点、getBy* 設計の根拠、あえて作らなかったもの
- `docs/resources.md` — 各リソースの URI / body shape / 応答コード
- `docs/alps.md` — ALPS 3層 + コードへのマッピング
- `CLAUDE.md` — 将来セッション用: コンテキスト、実行レシピ、2つのハマりポイント
  (FETCH_FUNC 列順、interceptor の getRow dispatch)

## 主要な設計判断 (要点)

1. **FakeSqlQuery は実装パッケージではなく差し替え** — `SqlQueryInterface` の
   自前実装を DI で bind。Ray.FakeQuery という専用パッケージは存在しない。
2. **FakeSqlQuery を `src/` に置く** — テスト専用ではなく `fake-hal-api-app`
   ランタイムコンテキストでも使うため、`tests/Fake/` から `src/Fake/` に移動。
3. **`#[Pager]` を使わない** — Pagerfanta+PDO依存の Pages を Fake化するコストを
   避け、Resource層で pagination メタを手動生成。
4. **Factory 不使用** — `FetchNewInstance` + 列順合わせで十分。Factory を
   入れるのは Entity に依存注入が必要になったタイミング。
5. **新ID 取得は `getBy{naturalKey}`** — driver依存の lastInsertId を回避。
6. **ALPS → Fake → 実SQL のパイプラインを bin/semantic-ex/ に保存** — 再生成
   可能、`random.seed(42)` で決定的。

## ハマったポイント (次回のため)

1. **`composer create-project` は空ディレクトリを要求** — `.claude/` 等の既存
   ファイルと衝突。一度 `/tmp` に生成してから `cp -R ./.` で移す回避策。
2. **ALPS の `tag` はスペース区切り文字列、配列は E011** — `alps-skills:alps`
   スキルのプロンプト例に配列があったので引っかかった。
3. **JSON Schema `format: date-time` は RFC3339 固定** — DBの `YYYY-MM-DD HH:MM:SS`
   形式とは別物。Fake側は ISO 8601 に合わせる必要あり。
4. **`DbQueryInterceptor` は exec を呼ばない** — `#[DbQuery]` の全メソッドは
   戻り型によって getRow/getRowList に振り分けられる。これを知らずに Fake の
   exec にだけ書き込み処理を置いて詰まった。
5. **モジュール構成変更時は `var/tmp/{context}/di/` の clear が必要** —
   DI ファクトリが古いまま残り、新しい binding が反映されない。

## コミット履歴

各フェーズを独立コミットに分割:

```
45e03d8 Phase 11: README + docs/ + CLAUDE.md
5c4f1e1 Phase 10: Resource tests for every App resource + Read/Write round-trip
8992864 Phase 9: Write (onPost/onPut/onDelete) across all App resources
64348f2 Phase 8: Add real SQL files — Read path works end-to-end against DB
51ac314 Phase 7: Doctrine Migrations config and seed script
c60caa2 Phases 5+6: App resources (Read) + Module wiring + malt infra
bc7d087 Phase 4: Add readonly Entities, MediaQuery Query interfaces, FakeSqlQuery
dfebe1e Phase 3: Generate Fake data (50 each) and JSON Schemas via semantic-ex
ead13ff Phase 2: Add ALPS profile for CMS (Read + Write transitions)
01f12f3 Phase 1: Initialize BEAR.Sunday skeleton
```

## 次に拡張するとしたら

- `#[Cacheable]` + QueryRepository (`bear/query-repository`) でHTTPキャッシュ
- `#[Pager]` に戻し、PagesInterface ベースのページング
- ArticleTag の関連付け API (タグ一括更新)
- `ray/input-query` を使った `#[Input]` 階層入力オブジェクト
- 認可レイヤ (Admin コンテキスト追加)
- トランザクション (ArticleTag 同期など)
