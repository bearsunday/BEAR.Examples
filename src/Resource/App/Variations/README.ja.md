# Article GET Variations

[English](README.md)

このディレクトリには、Article GET リソースの比較用実装を置いています。
正規のエンドポイントは `src/Resource/App/Article.php` です。ここにある
クラスは、実装方針の違いを読むための教材です。

デモは次のコマンドで実行できます。

```bash
composer demo:variations
```

## 見どころ

| クラス | 比較軸 | 見るポイント |
|---|---|---|
| `ArticleAsArray` | Entity と array | `#[DbQuery]` で取得した行を、そのままレスポンス配列へ整形します。短く書けますが、型変換やレスポンス shape の責務が Resource に残ります。 |
| `ArticleSqlQuery` | 宣言的 query と programmatic query | `SqlQueryInterface` を使い、複数の read と PHP 側の処理を Resource が組み立てます。reading time や previous/next のような付加情報がある場合の見本です。 |
| `ArticleRawPdo` | MediaQuery と raw PDO | `ExtendedPdoInterface` で SQL を直接実行します。DB アクセスの実体が見えやすい一方で、MediaQuery が SQL 探索、bind、fetch strategy、query 名管理を引き受けていることも分かります。 |

## 読む順番

1. `ArticleAsArray` で、最小の Article レスポンスを確認します。
2. `ArticleSqlQuery` で、Resource が複数 query を調停する場面を見ます。
3. `ArticleRawPdo` で、MediaQuery を外した場合の差分を確認します。

これらの Resource は ALPS profile には含めません。また、正規 API と並ぶ
別系統の API surface に育てないでください。
