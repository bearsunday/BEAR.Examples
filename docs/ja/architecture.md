# アーキテクチャ

[English](../architecture.md)

## 解像度を上げていくビルドアップ

コードはボトムアップで構築され、各フェーズで解像度を上げていきます。

```
ALPS (semantics)
   ↓ alps-skills:alps
Fake data (50/entity, with referential integrity)
   ↓ be-framework-skills:semantic-ex
JSON Schema (constraints derived from observation, not decided)
   ↓
BDR code — readonly entities + #[DbQuery] interfaces
   ↓
FakeSqlQuery (in-memory) — full Read+Write stack runs without a DB
   ↓
Doctrine Migrations + seed — real schema + same seed data
   ↓
SQL files — production backend; Fake and real produce the same App body shape
   ↓
Page resources + Qiq templates — HTML projection (公開ページは read-only、Page/Admin/* が App resource を wrap して write フォームを提供)
```

各ステップは単独でテスト可能です。テストは Fake (高速・hermetic) でも
real (DB) でも実行でき、どちらも同じ Resource コードを動かします。

## BDR pattern (Bound / Domain / Resource)

[BDR_PATTERN-ja.md](https://github.com/ray-di/Ray.MediaQuery/blob/1.x/BDR_PATTERN-ja.md) を参照してください。

| Layer     | Directory            | Role                                               |
|-----------|----------------------|----------------------------------------------------|
| Bound     | `src/Resource/App/*` | HTTP method binding、Link/Embed、validation gates  |
|           | `src/Resource/Page/*` | Qiq/Page HTML — 公開は read-only、`Page/Admin/*` が App resource を wrap して write フォーム |
| Domain    | `src/Entity/*`       | Final readonly classes: 不変データ                  |
| Resource  | `src/Query/*`        | `#[DbQuery]` Read interfaces → entity              |
|           | `src/Query/*`        | `#[DbQuery]` Write interfaces → `void`             |

ここでは Factory を使いません。最もシンプルなパスは `FetchNewInstance` 経由の
PDO::FETCH_FUNC で、SELECT のカラム順から entity を positional に構築します。
そのため `var/db/sql/` の SQL ファイルは、各 entity の `__construct` が期待する
順序でカラムを射影しています。

## Read と Write のディスパッチ

`DbQueryInterceptor` は、すべての `#[DbQuery]` メソッドを return type に基づいて
`SqlQueryInterface::getRow` または `getRowList` にルーティングします — interceptor
が `exec()` を呼ぶことは *ありません*。Ray.MediaQuery の本物の `SqlQuery` であれば
これで問題ありません: `perform()` が statement を実行し、非 SELECT に対しては
`[]` を返します。`FakeSqlQuery` でも同じ規約に従い、write を `getRow`/`getRowList`
の中でディスパッチします。

## なぜ bySlug / byEmail / byFilename なのか

`INSERT` の後、新しい行の id が必要になります。lastInsertId (driver 依存で fake
しにくい) を漏らす代わりに、各 Resource の `onPost` は client が直前に渡した
natural unique key を使って `by<NaturalKey>` メソッドを呼びます。これは可搬性が
あり (SQLite/MySQL/Postgres)、fake 可能で、Command interface の `void` 戻り値
を保てます。query メソッド命名規約 (`item` / `by<NaturalKey>` / `list`) の全体
ルールは `docs/conventions.md` §3 を参照してください。

## Contexts

BEAR.Sunday の `prod-hal-api-app` / `test-hal-api-app` 規約はそのまま使います。
追加分は次のとおりです。

- `fake-hal-api-app` — FakeModule をインストールするランタイム context。
  DB なしでアプリを動かせます (デモ用途など)。
- `test-hal-api-app` — TestModule が FakeModule を install します。
- `html-hal-app` / `cli-html-hal-app` — real DB を使う Qiq/Page HTML context。
- `html-test-hal-api-app` — PHPUnit の Page context。TestModule と HtmlModule を
  合成し、FakeSqlQuery に対して HTML を render します。

## 意図的に *作らなかった* もの

- 本番向けの完全な admin security model。`Page/Admin/*` は `AdminGuard`、
  `UserInterface` / `AdminUserInterface`、session-backed OAuth login、
  CSRF form protection で保護済みですが、author-scoped ownership を超える
  role model はこの reference slice の外です。
- JavaScript で拡張した管理操作
- canonical collection URI を超える query-string cache invalidation。custom な
  filter-variant invalidator は application policy としては妥当ですが、この
  reference では再利用可能な BEAR.Sunday primitive に cache example を絞ります。
- UI pager rendering の細かなカスタマイズ。Article collection はすでに
  Ray.MediaQuery `#[Pager]` を使い、Page template 側では compact な previous /
  next link を自前で描画します。

## See also

[conventions.md](conventions.md) — 「このコードベースでどうコードを書くか」の
companion。命名規約、body 構築スタイル、HAL rel 命名の分割
(Choreography vs Taxonomy)、ファイル配置など。
