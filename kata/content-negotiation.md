# `content-negotiation`

**AcceptヘッダでコンテキストをスイッチしJSON/HTML/CSV等を出し分ける** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** content negotiation, BEAR.Accept, #[Produces], Accept-Language, Vary, media type, コンテントネゴシエーション, 表現切替
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/content-negotiation.html
- **Use when:** 同一URIで `Accept` / `Accept-Language` に応じた表現（media type・言語）を返したい。
- **近いKata:** このリポジトリはcontextの静的分離（`hal-api-app`=HAL JSON / `html-hal-app`=Qiq HTML）で複数表現を提供している。実行時ネゴシエーションが必要な時のみこの型を使う。

## 例

このリポジトリに正規実装は無い（`bear/accept` は未インストール）。公式マニュアル [Content Negotiation](https://bearsunday.github.io/manuals/1.0/en/content-negotiation.html) を一次資料として `composer require bear/accept` の上で実装する。方式は2つ — アプリ全体で切り替えるなら `public/index.php` で `Accept` クラスに `Accept*` header を渡してcontextを決定し、header → context のマップは `var/locale/available.php` に置く。resource単位で切り替えるなら `AcceptModule` をinstallし、methodに `#[Produces(['application/hal+json', 'text/csv'])]` を宣言する。どちらの方式でもResourceのロジックは変わらず、representationの生成はcontextual rendererが担う。

## Naming

この型に固有の命名はBEAR.Accept側の規約が中心で、Query/Command等のアプリ命名には影響しない:

| 対象 | 命名 |
|---|---|
| header → context のマップ | `var/locale/available.php` |
| resource単位の宣言 | `#[Produces([...])]` + `AcceptModule` |
| 切替先 | 通常のcontext名（`hal-api-app` / `html-hal-app` 等） |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `composer require bear/accept` が必要（このリポジトリには未インストール）と確認したか。
- [ ] `Accept*` header → context のマップを `var/locale/available.php` に置くと理解したか。
- [ ] アプリ全体（`public/index.php` で `Accept` クラスを使いcontextを決定）かresource単位（`AcceptModule` + `#[Produces]`）かを選んだか。

## Key points

`#[Produces(['application/hal+json', 'text/csv'])]` は左から優先度順。resource単位では `Vary` headerが自動付与される（アプリ全体方式では手動）。representationの生成はcontextual rendererが担う。

## Do not

- `Vary` 無しでnegotiated responseをキャッシュさせない — アプリ全体方式では `Vary` は自動付与されない。`Vary: Accept` を欠くと、`Accept` の異なるクライアントに別表現のキャッシュが配られる。

## マスター確認（After）

- [ ] `Accept` header別に表現が切り替わり、`Vary` が付くことを自プロジェクトのtestでpinしてgreen。

## See also

- [`api-get-hal-resource`](./api-get-hal-resource.md) — `hal-api-app` contextでのHAL JSON表現（静的分離のJSON側）
- [`page-resource-qiq-detail`](./page-resource-qiq-detail.md) — `html-hal-app` contextでのQiq HTML表現（静的分離のHTML側）
- [`cacheable-response`](./cacheable-response.md) — 表現込みのresponseキャッシュ。negotiationと併用するなら `Vary` が前提
- [`conditional-request-304`](./conditional-request-304.md) — header駆動でresponseを制御するもう一つの型
- [`api-options-method`](./api-options-method.md) — 同じmanual-only群。メソッドとパラメータ仕様の実行時発見
