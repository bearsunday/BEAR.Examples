# `signed-url-verification`

**有効期限付き署名URLでメール検証リンクを実装する** · [← 索引に戻る](../index.md)

- **Category:** External reference（外部参照実装の型）
- **Status:** `external`
- **Aliases:** signed URL, URL signature, email verification, expiring link, magic link, 署名付きURL, メール検証, 有効期限付きリンク
- **Manual:** —（公式マニュアル章なし）
- **Reference:** [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `ddd/core/src/Domain/UrlSignature/`, `src/Resource/Page/Admin/EmailVerify.php`, `src/Resource/Page/Admin/Join.php`
- **Use when:** メールアドレス検証・招待・下書きプレビュー共有など、URLだけで一時的な権限を渡すリンクを発行したい。
- **近いKata:** [`admin-session-login`](./admin-session-login.md)（外部から戻ってくるフローの型）, [`error-status-mapping`](./error-status-mapping.md)（検証失敗例外→ステータスの振り分け）

## 例

参照実装は外部リポジトリ [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) にある（`ddd/core/src/Domain/UrlSignature/`、`src/Resource/Page/Admin/EmailVerify.php`、`src/Resource/Page/Admin/Join.php`）。同リポジトリにはライセンス表記が無いため**コードはコピーしない** — 構成・命名・責務分割という「型」を読み取り、自プロジェクトで再実装する。

実装する形: 有効期限＋対象（address等）＋randomを持つpayloadを `UrlSignatureEncrypterInterface` 相当のdomain serviceで暗号化/署名して不透明トークン化し、URLのクエリに載せる。平文のserialize文字列をURLに露出させない。検証Page Resource（bear-appの `EmailVerify` / `Join` に相当）はトークンを復号→期限/宛先を検証→本処理へ進む。検証失敗は `ExpiredSignatureException` / `InvalidSignatureException` のような型付き例外で投げ分け、[`error-status-mapping`](./error-status-mapping.md) の型でステータスへマッピングする。deserializeには `unserialize($s, ['allowed_classes' => false])` を使ってオブジェクト注入を防ぐ（またはserializeを避けてJSONを使う）。

## Naming

payloadの暗号化・検証まわりの対応（bear-appの型）:

| 役割 | 命名 |
|---|---|
| payloadの暗号化/復号を担うdomain service | `UrlSignatureEncrypterInterface` 相当（`Domain/UrlSignature/` 配下） |
| 期限切れの例外 | `ExpiredSignatureException` |
| 署名不正（改竄）の例外 | `InvalidSignatureException` |
| 検証Page Resource | 用途名そのまま（bear-appでは `EmailVerify` / `Join`） |

例外は「期限切れ」「改竄」「宛先不一致」を別々の型にする — 例外名がそのままUX分岐の語彙になる。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 有効期限＋対象（address等）を持つpayloadを暗号化/署名してURLに載せ、平文のserialize文字列を露出させないと決めたか。
- [ ] 検証失敗を `ExpiredSignatureException` / `InvalidSignatureException` のような型付き例外で投げ分け、ステータスへマッピングすると決めたか。
- [ ] deserialize時に `unserialize($s, ['allowed_classes' => false])` でオブジェクト注入を防ぐと理解したか（またはJSONを使う）。

## Key points

payload（expiry + address + random）を `UrlSignatureEncrypterInterface` 相当で不透明トークン化し、検証Page Resourceが復号→期限/宛先検証→本処理。期限切れ・改竄・宛先不一致で別々の例外を投げ、UXを分岐できる。

## Do not

- 期限なしの検証リンクを発行しない — payloadにexpiryを入れずトークン化すると、漏洩したリンクが永久に有効になる。有効期限は署名対象のpayloadに必ず含め、復号後に検証する。

## マスター確認（After）

- [ ] 正常リンクで検証成功、期限切れ・改竄リンクでそれぞれ期待するエラー応答になることを自プロジェクトのtestでpinしてgreen。

## See also

- [`admin-session-login`](./admin-session-login.md) — 外部から戻ってくるフローの型（リンク→検証→セッション確立）
- [`error-status-mapping`](./error-status-mapping.md) — 検証失敗例外→ステータスコードの振り分け
- [`auth-oauth-flow`](./auth-oauth-flow.md) — 外部からcallback URLで戻ってくる検証フローの同型
- [`resource-permission-authorization`](./resource-permission-authorization.md) — 同じexternal参照の認可gate（恒常的な権限。署名URLは一時的な権限）
- [`rate-limit-interceptor`](./rate-limit-interceptor.md) — 同じexternal参照。検証エンドポイントの総当たり対策
