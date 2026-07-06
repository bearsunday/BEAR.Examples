# `resource-permission-authorization`

**`#[RequiredPermission]`でリソース単位の権限を判定する** · [← 索引に戻る](../index.md)

- **Category:** External reference（外部参照実装の型）
- **Status:** `external`
- **Aliases:** RBAC, ACL, permission, #[RequiredPermission], authorization, role, 権限, 認可, ロール, アクセス制御
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/security.html
- **Reference:** [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `src/Annotation/RequiredPermission.php`, `src/Interceptor/AdminAuthorization.php`, `ddd/core/src/Domain/AccessControl/`
- **Use when:** author-ownership（[`admin-auth-boundary`](./admin-auth-boundary.md)）を超えて、ロール/権限（Read/Write/Privilege等）でリソース単位のアクセス制御をしたい。
- **近いKata:** [`admin-auth-boundary`](./admin-auth-boundary.md)（401=認証と403=認可の責務分離。ownership比較はそちら）

## 例

このリポジトリに正規実装は無い（external）。参照実装は [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — ライセンス表記が無いためコードはコピーせず、attribute × interceptor × domain object の責務分割という「型」を読み取って自プロジェクトで再実装する。

型の骨格: 要求権限は `#[RequiredPermission(resourceName, Permission::Write)]` のようにResource methodへattributeで宣言する。interceptorはattributeを読み、認証済みユーザーの `AccessControl::isAllowed()` に判定を委譲して、拒否なら403。attribute未付与のmethodはfail-closed — bear-appの `AdminAuthorization` はannotation不在で即throwする。Permissionはenum（Read / Write / Privilege等）で定義し、allow/denyルールの構築はResourceから分離したdomain objectに閉じる。attribute × interceptor × AOP bindの実装形はこのリポジトリの [`csrf-same-origin-protection`](./csrf-same-origin-protection.md) が流用できる。一次資料は [公式マニュアル security](https://bearsunday.github.io/manuals/1.0/en/security.html)。

## Naming

参照実装（bear-app）の語彙 — 宣言・調停・ルールが別の型に分かれる:

| 役割 | 型 | 例（bear-app） |
|---|---|---|
| 要求権限の宣言 | attribute（`TARGET_METHOD`） | `#[RequiredPermission(resourceName, Permission::Write)]` |
| 判定の調停 | interceptor | `AdminAuthorization` |
| 権限 | enum | `Permission`（Read / Write / Privilege） |
| allow/denyルール | immutableなdomain object | `AccessControl::isAllowed()` |

自プロジェクトで再実装する際、このリポジトリの流儀では interceptor は `<Attribute名>Interceptor`、束ねるModuleは `<Concern>Module`（→ [`csrf-same-origin-protection`](./csrf-same-origin-protection.md) の Naming）。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[RequiredPermission(resourceName, Permission::Write)]` のようにmethodへ要求権限を宣言し、interceptorが現在ユーザーの `AccessControl::isAllowed()` で判定すると決めたか。
- [ ] attribute未付与のmethodをfail-closed（Forbidden）にする方針を理解したか（bear-appの `AdminAuthorization` はannotation不在で即throw）。
- [ ] Permissionをenumで定義し、allow/denyルールの構築をResourceから分離すると決めたか。

## Source

参照実装は外部リポジトリ（コードはコピーしない — 型のみ読み取る）:

- [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `src/Annotation/RequiredPermission.php`, `src/Interceptor/AdminAuthorization.php`, `ddd/core/src/Domain/AccessControl/` *(external repo)*

## Key points

判定素材（resourceName × Permission enum）はattributeが持ち、interceptorは認証済みユーザーのAccessControlに委譲して403。ルール構築（`addResource()->allow()->deny()`）はimmutableなdomain objectに閉じる。

## Do not

- 権限判定をResource本体に散らさず、fail-openにもしない — ただし資源データ依存のownership比較は例外で、そちらは [`admin-auth-boundary`](./admin-auth-boundary.md) の `owns()` の型が担う。ロール判定（interceptor）とownership判定（Resource）を使い分ける。

## マスター確認（After）

- [ ] 権限のあるユーザーで200、無いユーザーで403、attribute未付与methodで403（fail-closed）を自プロジェクトのtestでpinしてgreen。

## See also

- [`admin-auth-boundary`](./admin-auth-boundary.md) — 401=認証と403=認可の責務分離。資源データ依存のownership比較はそちら
- [`csrf-same-origin-protection`](./csrf-same-origin-protection.md) — attribute × interceptor × AOP bindの正規形（このリポジトリ内の流用元）
- [`rate-limit-interceptor`](./rate-limit-interceptor.md) — 同じbear-app参照のattribute × interceptorゲート（試行回数制限）
- [`admin-session-login`](./admin-session-login.md) — 認可の前提となる認証済みユーザーをsessionに確立するログインフロー
- [`error-status-mapping`](./error-status-mapping.md) — 例外→ステータス（401/403等）のマッピング
