# 構築ログ

BEAR.Cms を ALPS → Fake → 実SQL の順に解像度を上げながら構築した記録。
各フェーズの作業内容、判断、躓いたポイントを残す。

## 前提

- プロジェクトディレクトリ: `/Users/akihito/git/BEAR.Cms`
- VENDOR/PACKAGE: `MyVendor/Cms`
- 開発DB: MySQL 8 (malt前提)、CI・試用は SQLite でも可
- 当初スコープ: App リソースのみ。後続セッションで read-only Qiq/Page HTML
  projection と未認証の Article 管理画面を追加。
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
`bin/semantic-ex/gen-fake.php` を書いて `mt_srand(42)` で再現可能に。
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

- `src/Query/*CommandInterface.php` — `#[DbQuery]` Write interface ×5
- `src/Query/*QueryInterface.php` に `bySlug` / `byEmail` / `byFilename` を追加
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

### Phase 10.5: Async `#[Embed]` 配線 (Step 5.5 解消)

`bear/async 0.3.0` リリースに伴い、handoff.md で deferred とされていた
Step 5.5 を取り込んだ。0.3.0 は `bear/resource ^1.32` を要求するが、
プロジェクトの `^1.17` 制約と composer 上の lock (1.31.1) は 1.32.0 への
アップグレードで矛盾なく解決した (`composer require bear/async:^0.3 -W`)。

導入内容は最小:

- `bin/async.php` — README 推奨形そのまま。
  `vendor/bear/async/bootstrap.php` を `require` し、`AppModule` には
  一切触らず `ParallelRuntimeModule` を override 経由で被せる。
- `composer async` スクリプト追加。

並列化されるのは `src/Resource/App/Article.php` の 3つの `#[Embed]`
(`author` / `category` / `tagList`) — それぞれ独立リソースなので
代表的な利得サイト。`Author` / `Category` / `Tags` の `onGet` 応答は
すべて scalar/array のみ (オブジェクト・closure・resource を返さない)
なので、ext-parallel のスレッド間ペイロードコピー制約を満たす。

**確認したこと:**
- `composer test` → 187 tests pass / 11 skip (MySQL 必須の Integration)。
  `bin/async.php` は opt-in なので sync テスト系列に一切影響しない。
- ext-parallel + ZTS が無い環境で `bin/async.php` を叩くと
  `BEAR\Async\Exception\ExtensionNotLoadedException` が即座に
  install 手順付きで投げられる — 安全に fail する。
- `bin/app.php` (sync) の動作は変わらず。

**未確認 (環境制約):**
- 並列実行そのものは ext-parallel + ZTS PHP 必須。当 CI/開発ホストに
  ext-parallel が無いため、AsyncLinker の経路を実際に通すには
  `vendor/bear/async/demo/` の Docker イメージか、別途
  `pecl install parallel` が要る。Article の 3 embeds の壁時計時間が
  本当に短縮されることの計測は次セッション以降の課題。

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
   可能、`mt_srand(42)` で決定的。

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

## 振り返り (率直な感想)

技術的な事実は上にまとめたので、ここでは進め方の所感を残す。

### よかったこと

- **解像度を段階的に上げる構築順がうまくはまった。** ALPS で意味、Fake で具体例、
  schema で制約、entity でドメイン、SQL でストレージ — と「次のフェーズに行く前に
  この層は確定」という区切りで進められた。後戻りがほとんど発生しなかった。
- **同じ Fake JSON が4箇所で再利用される構造が気持ちよかった。** schema 検証、
  FakeSqlQuery、Doctrine seed、観察ログ。普通なら fixture を別々に作るところを、
  単一データセットで通せた。これは semantic-ex 由来の副産物で、最初から狙った
  わけではない。
- **`fake-hal-api-app` を独立したランタイムコンテキストにしたのが地味に効いた。**
  「Fakeはテスト用」という思い込みを捨てて `src/Fake/` に置いた瞬間、デモ・動作
  確認・ドキュメント生成が一気に楽になった。テスト用コンテキストとは別物として
  扱うべき。

### 想定外だったこと

- **`DbQueryInterceptor` が `exec()` を呼ばない事実。** これは正直、ソースを
  読むまで分からなかった。Phase 9 で Fake の `exec` にだけ書き込み処理を書いて
  実行 → "unknown row sqlId 'create_article'" で詰まった。Ray.MediaQuery の
  内部規約 (writes も getRow を通る、SQLite が SELECT 検出で分岐する) を知らないと
  Fake は書けない。**先にソースを読むべきだった。** 1往復ぶん時間を使った。
- **`composer create-project` が「ディレクトリが空でない」と怒る挙動。** 自分で
  rmdir した直後に harness が `.claude/settings.local.json` を復元したらしく、
  再実行したらまた怒られた。`/tmp` に作ってコピーする回避策で逃げたが、初手で
  読みきれなかった。
- **JSON Schema の `format: date-time` が RFC3339 限定だったこと。** DB の
  `YYYY-MM-DD HH:MM:SS` をそのまま使えるつもりでいた。Fake 側だけ ISO 8601 に
  揃えて辻褄を合わせたが、本来は entity に DateTimeImmutable を持たせて表現を
  正規化すべきだろう。Phase 4 の早い段階で気づきたかった。

### あまりエレガントでなかった所

- **`onPost` で `bySlug` を後追い fetch する設計。** 動作は portable で
  Fake にも優しいが、INSERT 直後の SELECT は本当は Repository パターンで
  隠したい操作。今は Resource 層に「INSERT して再取得する」という手続きが
  そのまま見えている。BEAR ぽくない。次に手を入れるなら `ArticleService`
  あたりを切る。
- **`#[Pager]` を避けたこと。** 「PagesInterface を Fake 化するコストが高い」が
  理由だが、本来は Pagerfanta の `ArrayAdapter` を使えば Fake Pages を作れる。
  楽な道に逃げた自覚はある。プロダクションに昇格させるなら戻すべき。
- **`Articles` のレスポンスに `count` (今ページの件数) しか入れていない。**
  `totalCount` (全体件数) がないと UI でページネーション組めない。schema 側の
  `articleList.json` には `totalCount` があるのに、実装が追いついていない。
  実は最初の Read 試運転で「動いた!」で満足してしまい、レビュー漏れ。
- **malt を実際に動かしていない。** SQLite で代行確認したので `malt.json` は
  技術的には未検証。手元で試せていないものを README に書いているのは誠実でない。
  ユーザーが malt start したときに pdo_mysql 拡張が要るとか、port 衝突があるとか、
  実際に当たってみないと分からない部分が残っている。

### 設計判断の自己評価

- **Factory 不使用 (FetchNewInstance に任せる) は正解。** Entity に依存注入が
  必要になった時点で導入する、で良い。今は YAGNI を回避できた。
- **`getBy{naturalKey}` 採用は妥当だが完璧ではない。** UNIQUE 制約が絶対前提に
  なる。slug が変わる可能性のあるドメインだと破綻する。CMS としてはOK。
- **コンテキスト命名 `fake-hal-api-app` は良い選択だった。** BEAR.Sunday の
  既存規約 (`prod-`, `test-`) に違和感なく収まる。

### プロセス上の気付き

- **Plan モードを何度も書き直したのが結果的に良かった。** 最初は QueryLocator を
  入れていた、Phinx を採用していた、Write を入れていなかった、malt を
  「インフラ構築」と曖昧に書いていた、BEAR.Skills を入れていなかった、と毎回
  追加・訂正された。一発で完璧な計画は無理だが、対話で精度が上がった。
- **コミットを1フェーズ=1コミットで切ったのが整理に効いた。** あとで build-log を
  書くときに、`git log` がそのまま目次になった。意図して粒度を揃えた価値あり。
- **`var/tmp/{context}/di/` のキャッシュ消し忘れで何度かハマった。** モジュール
  構成を変えたのに古いファクトリが残って binding が反映されない。これは BEAR の
  特性なので、普段から意識する習慣をつけたい。

### もう一回やるなら

1. **Ray.MediaQuery のソース (DbQueryInterceptor / SqlQuery / FetchInterface) を
   Phase 4 着手前に通読する。** Fake の契約を後で書き直さずに済む。
2. **Phase 5 で `Articles` の応答 shape を `articleList.json` schema と
   突き合わせて validate する。** 抜け漏れに気づける。
3. **malt を実際にインストールして `malt start` まで動かす。** README に書く以上、
   ユーザーが一発で通る経路を確認しておくべき。
4. **`#[Pager]` + `ArrayAdapter` で Fake Pages を作るパスを最初から検討する。**
   逃げずに正攻法を試す。

### 全体としては

リファレンス実装としての目的 — 「BEAR.Sunday + ALPS + semantic-ex + Ray.MediaQuery
+ BDR を一気通貫で見せる小さなコードベース」— は達成できたと思う。
19テスト pass、2バックエンド (Fake / 実SQLite) で同じ body shape、各フェーズが
独立コミットになっていて教材としても読める。

ただし「実用にも使える」と言える品質には届いていない。totalCount 欠落、malt 未検証、
INSERT 後 SELECT のロジックが Resource にむき出し、エラーハンドリング (重複 slug の
409 化など) 未実装、`#[Cacheable]` 未配線。これらは「次に拡張するとしたら」に
書いた通りで、参照実装としてのスコープは越えている。

## 次に拡張するとしたら

- `#[Cacheable]` + QueryRepository (`bear/query-repository`) でHTTPキャッシュ
- `#[Pager]` に戻し、PagesInterface ベースのページング
- ArticleTag の関連付け API (タグ一括更新)
- `ray/input-query` を使った `#[Input]` 階層入力オブジェクト
- 認可レイヤ (Admin コンテキスト追加)
- トランザクション (ArticleTag 同期など)
