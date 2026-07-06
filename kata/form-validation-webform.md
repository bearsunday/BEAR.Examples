# `form-validation-webform`

**Ray.WebFormModuleでAOPフォームバリデーション** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** WebFormModule, `#[FormValidation]`, `#[InputValidation]`, Aura.Input, form class, vnd.error, `onPostValidationFailed`, フォームバリデーション
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/form.html
- **Reference:** [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `source/app/src/Resource/Page/Admin/Login.php`（`#[FormValidation]` + `onPostValidationFailed` callback）、`source/app/src/Form/Admin/*Form.php`（Contact / Fieldset / Multiple / Upload の4種のform demo）
- **Use when:** フィールド定義・検証ルール・描画helperを1つのform classに集約し、AOPで検証を差し込みたい。
- **近いKata:** [`admin-prg-form`](./admin-prg-form.md)（このリポジトリの正規形: JSON Schema + Input DTO + PRG + CSRF）。WebFormはそれに代わる別方式であり、同じ境界で併用しない。

## 例

このリポジトリに実装は無い。公式マニュアル [Form](https://bearsunday.github.io/manuals/1.0/en/form.html) を一次資料として実装する。`AbstractForm` を継承したform classの `init()` にfield定義（Aura.Input）と検証ルールを集約し、Resource methodに `#[FormValidation(form:, onFailure:)]` を付けてAOPで検証を差し込む。成功時はそのままmethod本体が実行され、失敗時は `onFailure` に指定したcallback（例: `onPostValidationFailed`）へ分岐してエラー付きでformを再描画する。HTMLを返さないAPI用途では `#[FormValidation]` の代わりに `#[InputValidation]` を使い、失敗を `ValidationException` として投げさせる。実装のdemoは Reference の [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) が参考になる（ライセンス未表示のためコードの転用はせず、形のみ参照する）。

## Naming

この型に固有の命名はリポジトリ規約ではなくWebFormModule側の慣習に従う:

| 対象 | 命名 | 例 |
|---|---|---|
| Form class | `<Name>Form`（`AbstractForm` 継承、`src/Form/` に置く） | `ContactForm` |
| 検証失敗callback | `on<Verb>ValidationFailed`（`onFailure:` で指定） | `onPostValidationFailed` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] このリポジトリの正規形（`#[JsonSchema(params:)]` + `#[Input]` DTO + PRG）で足りないか先に確認したか。
- [ ] `composer require ray/web-form-module` が必要（未インストール）と確認したか。
- [ ] form classは `AbstractForm` を継承し `init()` でfield/ruleを定義、検証は `#[FormValidation(form:, onFailure:)]` で差し込むと理解したか。

## Key points

検証失敗時は `onFailure` メソッドへ分岐する。API用途は `#[InputValidation]` → `ValidationException`（`$e->error` が vnd.error+json）。CSRFはopt-in — `SetAntiCsrfTrait`（form単位）または `#[CsrfProtection]`（action単位）で有効化し、いずれも無ければCSRF検証は行われない。

## Do not

- CSRF未設定のままwrite formを公開しない — WebFormModuleのCSRF検証はopt-inで、`SetAntiCsrfTrait` か `#[CsrfProtection]` を付けない限り一切行われない。「デフォルトで有効」な他フレームワークの感覚のまま置き忘れやすい。

## マスター確認（After）

- [ ] 検証成功/失敗の両経路（onFailure分岐またはValidationException）を自プロジェクトのtestでpinしてgreen。

## See also

- [`admin-prg-form`](./admin-prg-form.md) — このリポジトリの正規形のform実装（JSON Schema + Input DTO + PRG + CSRF）
- [`json-schema-validation`](./json-schema-validation.md) — 正規形側の検証（`#[JsonSchema]`）
- [`api-post-input-dto`](./api-post-input-dto.md) — 入力を `#[Input]` DTOで受ける正規形
- [`csrf-same-origin-protection`](./csrf-same-origin-protection.md) — このリポジトリのCSRF防御の型（token + Same-Origin interceptor）
- [`aop-validation-valid`](./aop-validation-valid.md) — `#[Valid]`/`#[OnValidate]`/`#[OnFailure]` によるもう1つのAOP検証（同じくmanual-only）
