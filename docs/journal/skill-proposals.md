# スキル提案 — BEAR.Skills 拡張案

BEAR.Cms 構築中に「これが skill として欲しかった」と感じた局面を整理し、
具体的なスキル定義案として残す。`bearsunday/BEAR.Skills` への提案として。

各スキル案には:
- **Trigger** (どんなフレーズで起動するか)
- **Inputs** (何を受け取るか)
- **Outputs** (何を生成するか)
- **Why** (今回の構築でどこで詰まったか / どう役立ったか)

を書く。

---

## 既存 skill との関係

現在 `bear-skills` プラグインで提供されている (と思われる) skill は未確認。
重複する場合は読み替え可。本書は「あったら欲しい」リストなので、既存と
被るものがあれば既存優先。

関連する既存 skill (確認済み):
- `alps-skills:alps` (ALPS 生成・検証)
- `alps-skills:alps-to-{sql,jsonschema,mock,openapi,graphql}` (ALPS 派生)
- `be-framework-skills:semantic-ex` (Fake → 制約発見)
- `be-framework-skills:be-semantic` (E2E flow)
- `be-framework-skills:be` (Be Framework Input/Semantic/Final)

---

## 提案スキル一覧

### 1. `bear-skills:scaffold-resource`

**Trigger:** 「Article リソースを作って」「ResourceObject を生成」
「app://self/article のスケルトンを書いて」

**Inputs:**
- リソース名 (PascalCase)
- 想定 HTTP メソッド (`get`, `post`, `put`, `delete`)
- 関連する Query / Command interface 名 (任意)

**Outputs:**
- `src/Resource/App/{Name}.php` (ResourceObject 継承クラス)
- メソッドスケルトン (引数型 + return type)
- `#[Link]` の雛形コメント
- 対応するテストクラス `tests/Resource/App/{Name}Test.php` (任意)

**Why:**
今回 9 リソースを書いたが、構造はほぼ同じ。手で書くと細部 (`Code::NOT_FOUND`,
`$this->code = ...`, `$this->body = [...]`, return type `static`) でブレる。
スケルトン生成があれば一貫性と速度が両立する。

---

### 2. `bear-skills:scaffold-bdr`

**Trigger:** 「Article entity と Query interface を BDR パターンで作って」
「{name} の Read 三点セット」

**Inputs:**
- エンティティ名 (PascalCase)
- フィールド一覧 (`id:int`, `slug:string`, `title:string` ...)
- (option) JSON Schema ファイルへのパス → そこから自動推論

**Outputs:**
- `src/Entity/{Name}.php` (`final readonly class`)
- `src/Query/{Name}QueryInterface.php` (`#[DbQuery]` 付き)
- `src/Command/{Name}CommandInterface.php` (Write 用、任意)
- `var/db/sql/get_{name}.sql`, `list_{names}.sql` のテンプレ (column 順がコンストラクタと
  揃う)

**Why:**
このプロジェクトで一番繰り返した作業。Entity と Query と SQL の三点が「列順を
揃える」という暗黙ルールで結ばれているので、手で書くたびに気を遣う。
JSON Schema から推論できるなら最高。

---

### 3. `bear-skills:scaffold-fake-sqlquery`

**Trigger:** 「FakeSqlQuery を作って」「Query interface を Fake 化」

**Inputs:**
- `src/Query/` ディレクトリ
- `var/fake/data-50.*.json` ディレクトリ

**Outputs:**
- `src/Fake/FakeSqlQuery.php`
  - `getRow` / `getRowList` の sqlId dispatch
  - JSON ファイルから entity への変換 (`to{Entity}()` private メソッド)
  - write 系の `mutate()` helper
  - `execLog` (テストアサーション用)

**Why:**
今回まさに 400 行近い FakeSqlQuery を手書きした。dispatch 表は機械的に
生成可能 (interface の `#[DbQuery]` を集めれば作れる)。
**特に重要なのは「writes は getRow/getRowList を通る」契約を守った骨格を
吐くこと** — これが分からず詰まったので、skill が canonical 実装を
出してくれると新規ユーザーは助かる。

---

### 4. `bear-skills:setup-doctrine-migrations`

**Trigger:** 「Doctrine Migrations をセットアップ」「マイグレーション環境
作って」

**Inputs:**
- ベンダー / パッケージ名 (composer.json から自動取得可)
- DB 種別 (mysql / sqlite / postgres)

**Outputs:**
- `migrations.php` (config)
- `migrations-db.php` (.env パース付き connection)
- `var/db/migrations/.gitkeep`
- `composer.json` に `doctrine/migrations` + `doctrine/dbal` 追加
- `bin/seed.php` (スケルトン: `var/fake/*.json` から INSERT)

**Why:**
スケルトン (bear/skeleton) には migrations が含まれない。手で書くと
`migrations-db.php` の DSN パースで詰まる (今回も書き直した)。定型作業の
typical な抽出案。

---

### 5. `bear-skills:add-context`

**Trigger:** 「fake context を追加」「test-app context を作って」

**Inputs:**
- context prefix (`fake`, `test`, `prod`, ...)
- override する binding (`SqlQueryInterface -> FakeSqlQuery` など)

**Outputs:**
- `src/Module/{Prefix}Module.php`
- 既存 `bin/app.php` への注釈コメント (どの context で動くかの情報)
- `CLAUDE.md` のコンテキスト表に新行追加

**Why:**
context の追加は1個目のときに「なぜこの命名? どこに置く?」で迷う。
スキル化されていれば「3 番目の context」を作るときも一貫した手順で進む。

---

### 6. `bear-skills:di-dump`

**Trigger:** 「DI binding を確認」「fake-hal-api-app の依存を見せて」

**Inputs:**
- context 文字列

**Outputs:**
- 標準出力に: `Interface → Concrete (in {Module})` のリスト
- 必要なら `dot` 形式 / `mermaid` 形式に出力

**Why:**
`var/tmp/{ctx}/di/*.php` を grep して binding を確認する作業を何度かやった。
DI の透明性は BEAR の弱点 (AOP + factory file 化) でもあるので、
visualize する skill があるとデバッグ時間が短くなる。

---

### 7. `bear-skills:check-fetch-order`

**Trigger:** 「entity と SQL の列順を検証」「FetchNewInstance 安全性チェック」

**Inputs:**
- `src/Entity/` ディレクトリ
- `var/db/sql/` ディレクトリ
- `src/Query/` (どの SQL がどの Entity を返すかをマッピング)

**Outputs:**
- 各 SQL ファイルについて、SELECT した列順と対応 Entity のコンストラクタ引数順が
  揃っているか検証
- ズレている箇所をエラーレポート

**Why:**
`FetchNewInstance` (PDO::FETCH_FUNC) は **位置引数** で entity を構築するため、
SELECT 列順とコンストラクタ引数順が揃っていないと "型は合うけど中身が壊れる"
バグが出る (デバッグが超大変)。今回は人力で気をつけたが、static check できる
領域。

---

### 8. `bear-skills:semantic-cycle`

**Trigger:** 「ALPS から実装まで一気通貫で」「semantic cycle 回して」

**Inputs:**
- 自然言語のドメイン記述 (またはユーザーストーリー)
- 出力ディレクトリ (default: `var/`)

**Pipeline:**
1. `alps-skills:alps` で `var/alps/profile.json` 生成
2. `be-framework-skills:semantic-ex` で `var/fake/*.json` + `var/json_schema/*.json` 生成
3. `bear-skills:scaffold-bdr` で entity / query interface / SQL skeleton 生成
4. `bear-skills:scaffold-resource` で App resource skeleton 生成
5. `bear-skills:scaffold-fake-sqlquery` で FakeSqlQuery 生成
6. テストの雛形を生成
7. 検証コマンド (`asd --validate`, `phpunit`) を実行

**Outputs:**
- 上記すべての成果物 (BEAR.Cms と同じレイアウト)

**Why:**
今回これを手動で 11 phase で回した。skill 化すれば「30 分でリファレンス
レベルの BEAR app が立ち上がる」が現実的になる。`be-framework-skills:be-semantic`
の BEAR.Sunday 版というイメージ。

---

### 9. `bear-skills:cache-config`

**Trigger:** 「Cacheable を配線」「リソースキャッシュを設定」

**Inputs:**
- 対象 Resource クラス
- TTL / 依存 URI / 無効化トリガ
- (option) CDN surrogate key 設定

**Outputs:**
- `#[Cacheable(expirySecond: ...)]` の付与
- `bear/query-repository` の Module 設定
- `#[Refresh]`/`#[Purge]` の対応 onPost/onPut/onDelete への注入

**Why:**
今回はあえて入れなかった層。配線が複雑で「何が rule of thumb か」を見つけるのに
時間がかかる。skill 化すれば適切なデフォルトで配線できる。

---

### 10. `bear-skills:malt-init`

**Trigger:** 「malt 環境を初期化」「ローカル開発インフラ生成」

**Inputs:**
- PHP バージョン
- DB 種別 (mysql / postgres)
- Web サーバ (nginx / apache)
- 追加サービス (redis / memcached)

**Outputs:**
- `malt.json` (適切な dependencies / ports / extensions)
- `.env.dist` (DB_DSN テンプレ)
- README.md の「Setup」セクションに malt 用手順を追加

**Why:**
今回 malt.json を手書きしたが、port 衝突やバージョン適合は手探り。
`malt --validate` 的な検証も skill 内で走らせると「書いて即動く」になる。

---

### 11. `bear-skills:resource-test`

**Trigger:** 「{Resource} のテストを書いて」「Resource Test scaffolding」

**Inputs:**
- 対象 Resource クラスパス
- テストパターン (`crud-roundtrip`, `read-only`, `embed-check` など)

**Outputs:**
- `tests/Resource/App/{Name}Test.php`
  - `AbstractAppTestCase` 継承
  - パターンに応じた testOnGet / testCreateUpdateDelete / test404 などの
    smoke テスト

**Why:**
今回 7 ファイル書いたが、構造は ほぼ コピペ + 差分。skill が出せば 5 分で 50
ファイル分作れる。

---

### 12. `bear-skills:diagnose-binding-error`

**Trigger:** 「Ray.Di の binding error を解読」「DI で詰まった」

**Inputs:**
- エラーメッセージ全文 (stack trace 含む)

**Outputs:**
- どの interface が解決できなかったか
- どの Module でその binding があるべきか
- 候補の修正案 (typo? 不足 install? scope mismatch?)
- `var/tmp/{ctx}/di/` のキャッシュ消し提案

**Why:**
Ray.Di のエラーは「直接的に何が悪いか」を言ってくれない (Reflection ベース)。
skill が経験則的な診断を出してくれると新規参入の hardest part が和らぐ。

---

## 優先順位 (個人的な)

「これがあったら今回 2-3 時間短縮できた」順:

1. **`bear-skills:scaffold-fake-sqlquery`** — Phase 4 で 1 番悩んだ箇所
2. **`bear-skills:check-fetch-order`** — sneaky bug の予防
3. **`bear-skills:scaffold-bdr`** — 5 エンティティで同じ作業を繰り返した
4. **`bear-skills:semantic-cycle`** — エンドツーエンド体験の格差を埋める
5. **`bear-skills:di-dump`** — デバッグ可視性

「あると嬉しいが今回は手動でも回せた」:

6. `bear-skills:scaffold-resource`
7. `bear-skills:resource-test`
8. `bear-skills:setup-doctrine-migrations`
9. `bear-skills:add-context`
10. `bear-skills:malt-init`

「将来的に必要だが今回 scope 外」:

11. `bear-skills:cache-config`
12. `bear-skills:diagnose-binding-error`

---

## 実装ガイドライン (skill 全体に通底すべき指針)

skill を作るときの原則として:

1. **既存の規約を強制しない、観察した挙動を再現する** — bear/skeleton の構造を
   そのまま延長する。新規 best practice を skill に潜ませない。
2. **生成物は必ず `php -l` を通す** — syntax error をユーザーに見せない。
3. **テスト雛形をセットで出す** — 生成した Resource / Query には対応テストも。
4. **`var/tmp/*/di/` の手動 invalidate を skill 終了時に実行** — Module を
   touch する skill は必ずキャッシュを消す。
5. **生成物に `# Generated by bear-skills:xxx` コメントは付けない** — 手書きと
   生成の区別がない方が良い (人間が後から自然に編集できる)。

---

## まとめ

BEAR.Sunday は概念が深いぶん、初動の認知負荷が高い。skill による足場掛けは
「思想を理解しないと書けない」を「思想を理解する前に動かせる、動かしながら
理解する」に変える効果がある。

特に `semantic-cycle` 系の orchestrator skill は、ALPS-first の方法論を
広める導線として強力になる可能性がある。BEAR.Sunday の差別化要因 (semantic-first)
を skill 化することで、競合フレームワークでは真似できない開発体験を提示できる。
