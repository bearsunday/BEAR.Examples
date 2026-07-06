# `api-patch-partial-update`

**PATCHで差分更新を受ける** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** PATCH, onPatch, partial update, delta update, 差分更新, 部分更新
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource.html
- **Use when:** リソース全体の置換（PUT）ではなく、差分のみを適用する更新を公開したい。
- **近いKata:** [`api-put-tristate-input`](./api-put-tristate-input.md)（省略=維持のtri-state入力の型はそのまま流用できる）

## 例

このリポジトリに正規実装は無い（manual-only）。[公式マニュアル（resource）](https://bearsunday.github.io/manuals/1.0/en/resource.html)を一次資料として読み、ResourceObjectに `onPatch` を実装する。`onPatch` はBEAR.Sundayのネイティブmethod（`onGet`/`onPost`/`onPut`/`onPatch`/`onDelete` の統一インターフェース）で、特別なmodule設定は不要。差分更新の入力設計 —「送られたフィールドだけ適用、省略されたフィールドは維持」— には [`api-put-tristate-input`](./api-put-tristate-input.md) のtri-state Input DTO（`null` = 維持）の命名・分離・テスト形をそのまま流用して移植する。マスター確認は自プロジェクトに書いたテストのgreenが最終確証。

## Naming

| 種別 | 命名 | 例 |
|---|---|---|
| Resource method | `on<HttpVerb>`（handlerはHTTP動詞順に並べる） | `onPatch` |
| Input DTO | `<Entity><Verb>Input` | `ArticleUpdateInput` |
| validation schema | `var/json_validate/<entity>_<verb>.json` | `article_update.json` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] PUT（全置換・冪等）とPATCH（差分適用・非冪等）の意味論の違いを理解したか。
- [ ] `onPatch` はBEAR.Sundayがネイティブ対応（`onGet`/`onPost`/`onPut`/`onPatch`/`onDelete` の統一インターフェース）と理解したか。
- [ ] 「省略されたフィールドは維持」の入力設計に [`api-put-tristate-input`](./api-put-tristate-input.md) のtri-state型を流用すると決めたか。

## Key points

method安全性表でPATCHは Safe=No / Idempotent=No。PUT/PATCH/DELETEのbodyは `content-type`（`application/json` / `x-www-form-urlencoded`）に応じて引数に渡る。

## Do not

- PATCHにPUTの全置換セマンティクスを持ち込まない — 差分適用の意味（維持/削除/置換）を暗黙にしない。

## マスター確認（After）

- [ ] 省略フィールドが維持され、指定フィールドのみ更新されることを自プロジェクトのtestでpinしてgreen。

## See also

- [`api-put-tristate-input`](./api-put-tristate-input.md) — 省略=維持のtri-state入力の正規実装（PATCHにそのまま流用）
- [`api-post-input-dto`](./api-post-input-dto.md) — 書き込み入力をInput DTOで受ける型
- [`api-delete-no-content`](./api-delete-no-content.md) — 同じ書き込み系methodのDELETE
- [`json-schema-validation`](./json-schema-validation.md) — 入力contractをschemaで宣言しresource boundaryで検証する
- [`api-options-method`](./api-options-method.md) — 利用可能methodとパラメータ仕様の実行時発見
