---
name: bear-kata
description: BEAR.Sunday実装の「型(Kata)」をソース索引から引く。「BEAR.Sundayで〜を実装したい」時に、どの正規形(canonical)ソースを真似し、どのテストで振る舞いを確認し、実装後にどうマスターを検証するかを案内する。Use when user says "〜をkataに従って実装してください", "kataに従って実装", "kata に従って", "BEAR.Sundayで〜を実装したい", "Kata", "ソース索引", "source index", "どのソースを見れば", "pager/HAL embed/streaming/PRG/Cacheable/CSRF/OAuth/ファイルアップロード/イベントソーシング/defer/FakeSqlQuery を実装", or asks which BEAR.Sunday reference implementation to copy/follow for a feature.
user-invocable: true
---

# bear-kata — BEAR.Sunday 実装の型を引く

このスキルは BEAR.Kata の **ソース索引** [`docs/source-index.md`](docs/source-index.md) を入口に、「これを実装したい」という intent から、真似してよい正規形ソース・確認すべきテスト・マスター確認チェックリストへ最短で到達させる。

索引は武道の「型(Kata)」の集まり。各 Kata は **着手前チェック → ソース → テスト → 実装 → マスター確認** を備える。

## いつ使うか

- 「BEAR.Sundayで○○を実装したい」（一覧/ページング、HAL link/embed、POST/PUT/DELETE、ストリーム応答、キャッシュ、PRGフォーム、CLI、OAuth認証、CSRF保護、ファイルアップロード、イベントソーシング、defer実行、エラーハンドリング、状態遷移 等）。
- 「どのソースを真似すればいい？」「このパターンの正規形は？」。
- 既存実装が型に従っているかレビューしたい。

## 手順（6ステップ）

1. **INTENT を言語化する。** ユーザーがやりたいことを1文にする。例:「記事一覧をページングして返したい」。
2. **ROUTE — 索引で Kata を引く。** [`docs/source-index.md`](docs/source-index.md) を開き、`Aliases`（`pager`, `#[Embed]`, `streaming`, `PRG`, `CSRF`, `OAuth`, `#[Defer]`, `event sourcing`, `FakeSqlQuery` 等）でマッチする Kata を選ぶ。冒頭の一覧テーブルからも引ける。
3. **Status を確認する。**
   - `canonical` … 最初に真似する正規形。コピー可。
   - `showcase` … 特定機能の実例。
   - `comparison-only` … 比較理解用。**デフォルト実装にコピーしない。**
   - `support` … テスト/Fake/生成物。
4. **着手前チェック（Before）を読む。** 書き始める前に守るべき型と前提（命名・分離・契約）を確認する。
5. **READ → OBSERVE。** `Source` を読んで `Key points` と `Do not` を把握し、`Tests` で期待される振る舞い（status / body / link / embed）を確認する。
6. **IMPLEMENT → MASTER。** ユーザーのプロジェクトに移植したら、**マスター確認（After）** のチェックリストを実装に対して走らせる。最終確証は「`Tests` に挙げたテストを自分の実装へ写経して green になること」。全項目 ✓ なら、その Kata をマスターしたと判断する。

## 索引の構造（各 Kata の項目）

```
- ID / Aliases / Status / Use when
- 着手前チェック（Before）  … 書く前に確認する型・前提
- Source                    … 真似してよいソース（このリポジトリの実パス）
- Tests                     … 正しい振る舞いの仕様
- Key points / Do not       … 要点とアンチパターン
- マスター確認（After）      … greppable assertion ＋ test green
```

## 注意

- `comparison-only` の Kata（`db-array-row-comparison`, `db-sqlquery-orchestration`, `db-raw-pdo-comparison`）は**理解用**。正規形として移植しない。
- 索引のパスはこのリポジトリ（BEAR.Kata）内の実ファイル。別プロジェクトへ移植する際は、命名規約（`docs/conventions.md`）と型を保ったまま自分の Entity 名へ読み替える。
- **別プロジェクトでこのスキルを使う場合**、ローカルに `docs/source-index.md` が無いことがある。その時は GitHub のコピーを参照する: `https://github.com/bearsunday/BEAR.Examples/blob/1.x/docs/source-index.md`（raw: `https://raw.githubusercontent.com/bearsunday/BEAR.Examples/1.x/docs/source-index.md`）。`Source` / `Tests` のパスも同じリポジトリの該当ファイルとして読む。
- 索引に該当 Kata が無い時は、近い Status=`canonical` の Kata の型（Query/Command 分離、SQL外部化、ResourceObject body/status、HAL rel 層分離）を当てはめ、`docs/conventions.md` を参照する。
