# `rate-limit-interceptor`

**`#[RateLimiter]`×interceptorで試行回数を制限する** · [← 索引に戻る](../index.md)

- **Category:** External reference（外部参照実装の型）
- **Status:** `external`
- **Aliases:** rate limit, throttling, #[RateLimiter], 429, Too Many Requests, brute force, レート制限, スロットリング, 総当たり対策, アカウントロック
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/aop.html （AOPの一次資料）
- **Reference:** [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) — `src/Annotation/RateLimiter.php`, `src/Interceptor/Throttling.php`, `ddd/core/src/Domain/Throttle/`
- **Use when:** ログインや公開write APIへの試行回数をURI×IP単位で制限し、超過を429で拒否したい。
- **近いKata:** [`csrf-same-origin-protection`](./csrf-same-origin-protection.md)（attribute × interceptor × AOP bindの実装形はこれを流用）

## 例

参照実装は外部リポジトリ [apple-x-co/bear-app](https://github.com/apple-x-co/bear-app) にある（`src/Annotation/RateLimiter.php`、`src/Interceptor/Throttling.php`、`ddd/core/src/Domain/Throttle/`）。同リポジトリにはライセンス表記が無いため**コードはコピーしない** — attribute × interceptor の構成・命名・責務分割という「型」を読み取り、自プロジェクトで再実装する。

実装する形: `limit` / `interval` を引数に持つ `#[RateLimiter]` attributeをwrite methodに付与し、AOP bindしたinterceptorが `getAnnotation()` でポリシーを読む。interceptorはURI×IPから制限キー（例: `sha1($uri . '|' . $remoteIp)`）を作り、カウント・判定はdomain service（`ThrottlingHandlerInterface` 相当）に委譲して自身は調停のみを行う。超過なら429を設定して例外、通過なら `countUp()` して `proceed()`。attribute × interceptor × AOP bindの組み立ては [`csrf-same-origin-protection`](./csrf-same-origin-protection.md) の実装形を流用する。AOPの一次資料は[公式マニュアル](https://bearsunday.github.io/manuals/1.0/en/aop.html)。

## Naming

attribute → interceptor → domain service の対応（bear-appの命名）:

| 役割 | 命名 |
|---|---|
| Policyを持つattribute（`TARGET_METHOD`） | `RateLimiter`（`limit` / `interval` が引数） |
| Interceptor | `Throttling` |
| カウント・判定のdomain service | `ThrottlingHandlerInterface` |

このリポジトリの流儀（[`csrf-same-origin-protection`](./csrf-same-origin-protection.md) の `<Attribute名>Interceptor`）に合わせるなら、interceptorは `RateLimiterInterceptor` になる。

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] ポリシー（`limit` / `interval`）はattribute引数に持たせ、interceptorは `getAnnotation()` で読むと決めたか。
- [ ] 制限キーの単位（例: `sha1($uri . '|' . $remoteIp)`）とカウンタの保存先（DB/cache）を決めたか。
- [ ] カウント・判定はdomain service（`ThrottlingHandlerInterface` 相当）に委譲し、interceptorは調停のみにすると決めたか。

## Key points

`#[RateLimiter(limit: 10, interval: '30 minutes')]` をwrite methodに付与。interceptorは超過なら429を設定して例外、通過なら `countUp()` して `proceed()`。intervalはPHPのrelative format文字列。ログイン失敗の恒久ロック（account lock）も同じThrottle domainの応用。

## Do not

- `X-Forwarded-For` を無検証で信頼しない — 信頼できるproxy配下でのみ使用し、それ以外は `REMOTE_ADDR` を使う。偽装可能なヘッダを制限キーのIPに使うと、rate limitは実質無効化される。

## マスター確認（After）

- [ ] 上限超過で429、interval経過後に回復することを自プロジェクトのtestでpinしてgreen。

## See also

- [`csrf-same-origin-protection`](./csrf-same-origin-protection.md) — attribute × interceptor × AOP bindの実装形（この型の流用元）
- [`aop-validation-valid`](./aop-validation-valid.md) — attribute + interceptorでmethodにgateを掛ける同型（validation）
- [`resource-permission-authorization`](./resource-permission-authorization.md) — 同じexternal参照の認可gate（403）
- [`error-status-mapping`](./error-status-mapping.md) — 例外→ステータスコードの対応付け（429の返し方の型）
- [`admin-session-login`](./admin-session-login.md) — レート制限が守る代表的対象（ログイン試行）
