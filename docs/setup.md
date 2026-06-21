# セットアップ・開発ガイド

## 要件

- PHP 8.5
- Composer
- Node.js 20+ と npm（`composer doc` / ALPS図生成時）
- オプション: MySQL（Malt または docker-compose）
- オプション: SQLite（簡易実DB確認）

## セットアップ

### Malt（macOS）

```bash
brew tap koriym/malt && brew install malt
malt install && malt create && malt start
source <(malt env)

cp .env.dist .env       # DB_DSN / DB_USER / DB_PASSWORD を編集
vendor/bin/doctrine-migrations migrate --no-interaction
php bin/seed.php
composer serve:api      # HAL JSON API: http://127.0.0.1:8080
composer serve          # Qiq/Page HTML: http://127.0.0.1:8081
```

### docker-compose（クロスプラットフォーム）

```bash
composer docker:up
cp .env.dist .env
```

`composer docker:up` は MySQL のみ起動してマイグレーション/シードを実行する。
並列実行コンテナは明示的に: `composer parallel:up`（ext-parallel）、`composer swoole:up`（Swoole）。

### SQLite（CI / 簡易確認）

```bash
rm -f /tmp/bear_cms.db
DB_DSN="sqlite:/tmp/bear_cms.db" vendor/bin/doctrine-migrations migrate --no-interaction
DB_DSN="sqlite:/tmp/bear_cms.db" php bin/seed.php
DB_DSN="sqlite:/tmp/bear_cms.db" composer serve:api
```

### 管理者ログイン

Google OAuth + PHPセッションで認証する。`.env` と Google OAuth クライアントの両方にコールバックURLを設定する。

```dotenv
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://127.0.0.1:8081/admin/callback
```

Pageサーバー起動後、`http://127.0.0.1:8081/admin/login` からサインイン。
認証済みGoogleメールが `authors.email` 行に一致する必要がある。
Auth0/OIDC は `CMS_AUTH_PROVIDER=auth0` で切り替え可能。
詳細は [auth-google.md](auth-google.md) を参照。

## サーバー起動

```bash
# terminal 1
composer serve           # Qiq/Page HTML: http://127.0.0.1:8081

# terminal 2
composer serve:api       # HAL JSON API: http://127.0.0.1:8080
```

## ランタイムコンテキスト

| コンテキスト | 用途 | DB |
|---|---|---|
| `hal-api-app` | 本番 HAL JSON HTTP | 必要 |
| `html-hal-app` | 本番 Qiq/Page HTML HTTP | 必要 |
| `cli-hal-api-app` | `composer app` / `bin/app.php` | 必要 |
| `cli-html-hal-app` | `composer page` / `bin/page.php` | 必要 |
| `fake-hal-api-app` | FakeSqlQuery を使った開発用 | 不要 |
| `test-hal-api-app` | PHPUnit App リソーステスト | 不要 |
| `html-test-hal-api-app` | PHPUnit Page/Qiq テスト | 不要 |

`fake-` は [src/Module/FakeModule.php](../src/Module/FakeModule.php)、`test-` は [src/Module/TestModule.php](../src/Module/TestModule.php) を先頭に追加する。

## 開発コマンド

```bash
composer test       # PHPUnit（MySQL なしでも実行可）
composer tests      # cs + 静的解析 + PHPMD + PHPUnit
composer fake       # var/fake/*.json を再生成
composer schema     # var/json_schema/*.json を fake から再生成
composer semantic   # fake → schema（semantic-ex フルパス）
composer doc        # ApiDoc / OpenAPI / llms.txt / ALPS HTML+SVG を再生成
composer cli        # #[Cli] 属性から bin/cli/* を再生成
composer serve      # Qiq/Page HTML サーバー :8081
composer serve:api  # HAL JSON API サーバー :8080
```

`#[Cli]` 属性から生成された CLI コマンド:

```bash
composer cli
bin/cli/article-show -i 1
bin/cli/article-list -s published -n 5
```

## API ドキュメント

`composer doc` で `docs/` 以下に生成される:

- [index.html](index.html) — URI / request / response / `_links` / `_embedded` マップ
- [alps.html](alps.html) / [alps.svg](alps.svg) — ALPS セマンティックプロファイルと状態遷移図
- [openapi.json](openapi.json) — OpenAPI 仕様
- [llms.txt](llms.txt) — LLM向け API サマリー
- [audit.md](audit.md) — ドキュメントカバレッジレポート
