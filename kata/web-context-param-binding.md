# `web-context-param-binding`

**Webコンテキスト値と他Resource値をmethod引数に束縛する** · [← 索引に戻る](../index.md)

- **Category:** Manual-only（型のみ記述 — 公式マニュアル準拠）
- **Status:** `manual-only`
- **Aliases:** #[CookieParam], #[QueryParam], #[FormParam], #[ServerParam], #[EnvParam], #[ResourceParam], web context binding, superglobal binding, クッキー束縛
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** `$_COOKIE` / `$_SERVER` / `$_ENV` の値や他Resourceの結果を、method内で取得せず引数として宣言的に受けたい。
- **近いKata:** [`api-post-input-dto`](./api-post-input-dto.md)（引数境界の宣言的設計という同じ型）

## 例

このリポジトリに正規実装は無い（manual-only）。公式マニュアル [Resource Parameters](https://bearsunday.github.io/manuals/1.0/en/resource_param.html) を一次資料として実装する。superglobalをmethod内で直読みする代わりに、`#[CookieParam('id')] string $tokenId = "0000"` のように属性で引数へ束縛する — attribute引数がsuperglobalのキー、default値がunset時の挙動の宣言になる。属性群は `bear/resource` に同梱されており（`vendor/bear/resource/src-web-context/Annotation/`）、追加インストールは不要。他Resourceの値は `#[ResourceParam('app://self//login#nickname')]` で受ける — URI fragmentが対象resourceのbody key。client指定値がある場合はそちらが優先されるため、testからは通常の引数として上書きできる。

## Naming

この型の命名は attribute → 束縛元 の対応で決まり、アプリ側のQuery/Command命名には影響しない:

| Attribute | 束縛元 |
|---|---|
| `#[QueryParam('key')]` | `$_GET['key']` |
| `#[CookieParam('key')]` | `$_COOKIE['key']` |
| `#[FormParam('key')]` | `$_POST['key']` |
| `#[ServerParam('key')]` | `$_SERVER['key']` |
| `#[EnvParam('key')]` | `$_ENV['key']` |
| `#[ResourceParam('uri#key')]` | 対象resourceのbody（URI fragment = body key） |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] superglobal直読みをやめ、`#[CookieParam('id')] string $tokenId = "0000"` のように引数束縛（default値でunset時の挙動を明示）にすると決めたか。
- [ ] client指定値が優先される（テストで上書き可能）と理解したか。
- [ ] 他Resourceの値は `#[ResourceParam('app://self//login#nickname')]`（URI fragment = body key）で受けると理解したか。

## Key points

`Ray\WebContextParam\Annotation\{QueryParam, CookieParam, EnvParam, FormParam, ServerParam}` で `$_GET`/`$_COOKIE`/`$_ENV`/`$_POST`/`$_SERVER` を束縛。`#[ResourceParam]` はmethod呼び出し時に対象resourceへGET requestを発行する。enum型引数は値を制限し、範囲外は `ParameterInvalidEnumException`。

## Do not

- `#[ResourceParam]` で重いresourceを無自覚に毎回呼ばない — method呼び出しのたびに対象resourceへGET requestが発行される。

## マスター確認（After）

- [ ] method内にsuperglobal参照が無く（grepで空）、引数束縛のみで値が渡ることを自プロジェクトのtestでpinしてgreen。

## See also

- [`api-post-input-dto`](./api-post-input-dto.md) — 引数境界の宣言的設計という同じ型（近いKata）
- [`json-schema-validation`](./json-schema-validation.md) — 同じ引数境界をschemaで宣言的に制約する
- [`hal-embed`](./hal-embed.md) — 他Resourceの値を取り込むbody側の型（`#[ResourceParam]` の引数側に対して）
- [`aop-validation-valid`](./aop-validation-valid.md) — 引数を横断的に検証するAOPの型
- [`api-options-method`](./api-options-method.md) — 同じmanual-only群。`on*` signatureからのパラメータ仕様の実行時発見
