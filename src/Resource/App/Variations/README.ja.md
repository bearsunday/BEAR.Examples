# Resource Variations

[English](README.md)

このディレクトリには、Article GET リソースの比較用実装を置いています。
正規のエンドポイントは `src/Resource/App/Article.php` です。ここにある
クラスは、実装方針の違いを読むための教材です。

これらは競合する API 設計案ではありません。同じようなレスポンス shape を
保ったまま、1つずつ軸を変えた読み物です。どの責務がどこへ移動するかを見る
ためにあります。

`MediaStream` は、Article の比較実装とは別枠のストリーム転送例です。
4つ目の Article 実装を増やすものではありません。通常の
`src/Resource/App/Media.php` は JSON を返すままにして、同じ Media の情報を
`BEAR.Streamer` でストリームとして転送する方法を示します。

ストリームのデモでは、media id `5` に対応する固定フィクスチャを使います。
それ以外の fake media はメタデータだけを持つため、ファイルが見つからない
場合の分岐を確認するのに使えます。

デモは次のコマンドで実行できます。

```bash
composer demo:variations
```

## 読みどころ

読む時は、次の3点に注目します。

- Article の不変条件がどこにあるか: Entity、Resource、SQL row、DB access。
- Resource がどれだけ自分でレスポンスを整形しているか。
- MediaQuery が Resource から何を取り除いているか: SQL 探索、parameter bind、
  fetch mode、query naming。

## 各クラスの見どころ

| クラス | 比較軸 | 見るポイント |
|---|---|---|
| `ArticleAsArray` | Entity と array | Entity を使わない最小版です。array shape、cast、datetime normalization が明示され、`Article` object が担っていた意味が Resource 側へ移ることを見ます。 |
| `ArticleSqlQuery` | 宣言的 query と programmatic query | 1つの `#[DbQuery]` では収まらない場面です。reading time と previous/next によって、Resource が複数 query の結果を組み立てます。 |
| `ArticleRawPdo` | MediaQuery と raw PDO | フレームワークの助けを外した DB access です。SQL がクラス内に見える代わりに、MediaQuery が普段隠している責務も見えるようになります。 |
| `MediaStream` | renderer と stream transfer | `StreamTransferInject`、明示的な content headers、open file handle を `$this->body` に入れる形を見ます。success body は JSON ではなく stream なので、意図的に `#[JsonSchema]` は付けません。 |

## 読む順番

1. `ArticleAsArray` で、最小の Article レスポンスを確認します。
2. `ArticleSqlQuery` で、Resource が複数 query を調停する場面を見ます。
3. `ArticleRawPdo` で、MediaQuery を外した場合の差分を確認します。
4. `MediaStream` は別枠です。Article response modelling ではなく transfer
   mechanics を確認したい時に読みます。
