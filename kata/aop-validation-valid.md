# `aop-validation-valid`

**`#[Valid]`/`#[OnValidate]`/`#[OnFailure]`でAOPバリデーション** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** #[Valid], #[OnValidate], #[OnFailure], Ray.ValidateModule, AOP validation, validation separation, 検証分離
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/validation.html
- **Use when:** 検証ロジックをmethod本体からAOPで分離したい（Aura.Filter / Respect\Validation等と組み合わせ可能）。
- **近いKata:** [`json-schema-validation`](./json-schema-validation.md)（このリポジトリの正規形。宣言的スキーマで表現できる形状検証はそちら）

## 例

このリポジトリに正規実装は無い（manual-only）。[公式マニュアル（validation）](https://bearsunday.github.io/manuals/1.0/en/validation.html) を一次資料として読み、`composer require ray/validate-module` の上で `ValidateModule` をinstallする。検証したいmethodに `#[Valid]` を付け、元methodと同じ引数を取る `#[OnValidate]` methodが `Validation` を返す — これで検証ロジックがmethod本体からAOPで分離される。失敗時は `#[OnFailure]` methodへ分岐し（無ければ `InvalidArgumentException`）、Aura.Filter / Respect\Validation等の検証ライブラリと組み合わせられる。形状検証が宣言的スキーマで表現できるなら、このリポジトリの正規形である [`json-schema-validation`](./json-schema-validation.md) を使う。マスター確認は自プロジェクトに書いたテストのgreenが最終確証。

## Naming

この型に固有のクラス命名は無い — 対応はattribute名の一致で取る:

| 役割 | Attribute |
|---|---|
| 検証対象method | `#[Valid]` / `#[Valid('foo')]` |
| 検証method（元methodと同じ引数を取り `Validation` を返す） | `#[OnValidate]` / `#[OnValidate('foo')]` |
| 失敗handler | `#[OnFailure]` / `#[OnFailure('foo')]` |

同名（`'foo'`）同士が1組として束ねられる。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `composer require ray/validate-module` が必要（未インストール）と確認したか。
- [ ] `#[OnValidate]` methodは元methodと同じ引数を取り `Validation` を返す、失敗時は `#[OnFailure]` へ分岐（無ければ `InvalidArgumentException`）と理解したか。

## Key points

`#[Valid('foo')]` の名前付きで1クラス複数バリデーションを共存できる。`#[OnFailure]` では `$failure->getMessages()` / `$failure->getInvocation()` で失敗詳細と元呼び出しへアクセスできる。

## Do not

- JSON Schemaで表現できる形状検証をAOP validationに重複させない — 宣言的スキーマで書ける検証は [`json-schema-validation`](./json-schema-validation.md) が正規形。AOP validationは手続き的にしか書けない検証のために残す。

## マスター確認（After）

- [ ] 検証分離後もmethod本体に検証分岐が残っていないことを確認し、成功/失敗経路を自プロジェクトのtestでpinしてgreen。

## See also

- [`json-schema-validation`](./json-schema-validation.md) — このリポジトリの正規形。形状検証を宣言的スキーマで行う
- [`form-validation-webform`](./form-validation-webform.md) — 同じAOP検証系のRay.WebFormModule（form class + `#[FormValidation]`）
- [`api-post-input-dto`](./api-post-input-dto.md) — 書き込み入力をInput DTOで受ける入力境界の型
- [`admin-prg-form`](./admin-prg-form.md) — validation失敗時に422でform再描画するこのリポジトリの失敗経路
- [`rate-limit-interceptor`](./rate-limit-interceptor.md) — 同じattribute×interceptorのAOP機構の別応用
