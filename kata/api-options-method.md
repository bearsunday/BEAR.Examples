# `api-options-method`

**OPTIONSでメソッドとパラメータ仕様を返す** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** OPTIONS, OptionsMethodModule, Allow header, method discovery, API discovery, メソッド発見
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/tutorial.html （OPTIONS応答例）, https://bearsunday.github.io/manuals/1.0/en/production.html （本番でのenable）
- **Use when:** クライアントが利用可能なHTTPメソッドと必要パラメータを実行時に発見できるようにしたい。
- **近いKata:** [`json-schema-validation`](./json-schema-validation.md)（パラメータ仕様の宣言はsignatureとschemaが担う）

## 例

このリポジトリに正規実装は無い（manual-only）。書くコードが無いのがこの型の要点 — OPTIONS応答は `onOptions` を自分で実装するのではなく、Resourceの `on*` signatureから自動生成される。`curl -i -X OPTIONS <uri>` で `Allow` headerとmethod別のparameters/required（型付き）JSONが返る様子は [公式マニュアル tutorial](https://bearsunday.github.io/manuals/1.0/en/tutorial.html) の応答例を参照。devコンテキストでは有効で、productionでは [production マニュアル](https://bearsunday.github.io/manuals/1.0/en/production.html) のとおり `$this->override(new OptionsMethodModule)` で明示的にenableする（backing classは `vendor/bear/resource/src/Module/OptionsMethodModule.php`）。

## Naming

この型に固有の命名は無い — OPTIONS応答の内容はResourceの通常の命名から導出される:

| Resource側 | OPTIONS応答 |
|---|---|
| public `on*` handler（`onGet` / `onPost` / …） | `Allow: GET, POST, …` |
| 引数名（`$id` など） | method別 `parameters` のキーと型 |
| デフォルト値の無い引数 | `required` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] OPTIONS応答（`Allow` header + method別のparameters/required JSON）はResourceのsignatureから自動生成され、自分で `onOptions` を書かないと理解したか。
- [ ] devコンテキストでは有効、productionでは `$this->override(new OptionsMethodModule)` で明示的にenableすると理解したか（backing classは `vendor/bear/resource/src/Module/OptionsMethodModule.php`）。

## Source

- [`vendor/bear/resource/src/Module/OptionsMethodModule.php`](../vendor/bear/resource/src/Module/OptionsMethodModule.php) *(external package)*

## Key points

`curl -i -X OPTIONS <uri>` が `Allow` headerとmethod別のparameters/required（型付き）をJSONで返す（RFC7231 §4.3.7）。OPTIONSはGET同様にsafe/idempotent。

## Do not

- OPTIONS応答を静的ファイルで手書きしない — Resource signatureがSSOT。手書きするとsignatureの変更に追従しなくなる。

## マスター確認（After）

- [ ] `curl -i -X OPTIONS` で `Allow` headerとparameters JSONが返り、Resource signatureの変更に追従する。

## See also

- [`json-schema-validation`](./json-schema-validation.md) — パラメータ仕様の宣言（signature + schema）の正規形
- [`api-get-hal-resource`](./api-get-hal-resource.md) — OPTIONSが報告する `on*` signatureの基本形
- [`hal-link`](./hal-link.md) — 実行時発見のもう一方（メソッドではなく遷移の発見）
- [`apidoc-llms-generated`](./apidoc-llms-generated.md) — 同じsignature/schemaをSSOTにしたビルド時のAPI資料生成
- [`content-negotiation`](./content-negotiation.md) — 同じHTTP機構レイヤのmanual-only型（表現の選択）
