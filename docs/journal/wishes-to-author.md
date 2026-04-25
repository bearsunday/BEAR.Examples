# 作者への質問・希望・要望リスト

BEAR.Cms 構築中に浮かんだ、BEAR.Sunday / Ray.MediaQuery / 関連エコシステムに
対する質問、希望、要望をまとめる。作者 (akihito koriyama) に向けたメモ。

率直に書く。整っていない疑問もそのまま残す。

---

## 質問 (Questions)

### Q1. `DbQueryInterceptor` が `exec()` を呼ばない設計意図は?

`SqlQueryInterface::exec(): void` は存在するが、`#[DbQuery]` 経由では一切
呼ばれない。INSERT/UPDATE/DELETE も `getRow` を通り、内部 `perform()` で
SELECT 検出して結果を `[]` にする実装。

これは:
- (a) 互換性の都合で残った歴史的経緯
- (b) 「全 DB 操作は同じ穴を通す」という設計上の選択
- (c) `exec()` は別の用途 (直接呼び) のために用意

のどれだろうか? (b) なら美しいが、その意図がドキュメント化されていないので
Fake を書くときに迷子になる。

### Q2. Entity の生成に `FetchNewInstance` (PDO::FETCH_FUNC) を使う設計の理由は?

`PDO::FETCH_CLASS` ではなく `FETCH_FUNC` を選んでいる。理由は: コンストラクタが
ある場合に位置引数で `new $entity(...$args)` できるから、と推測。これは合っているか?

副作用として「SELECT 列順 = コンストラクタ引数順」の暗黙ルールが生まれる。
これが破れたときのエラーメッセージは現状やや不親切なので、Reflection で順序を
合わせる将来案はあるか?

### Q3. `#[Pager]` を Fake する canonical な方法は?

`PagesInterface` を返す `getPages()` は Pagerfanta + ExtendedPdo に強く結びついている。
今回は Fake 化を諦めて Pager を使わない選択をしたが、本来どうするのが BEAR 流か?

候補:
- `ArrayAdapter` を使った Fake `Pages` を書く
- `getPages()` を別 interface に切り出して mock しやすくする
- 今のまま「Fake では Pager は使わない」が正解

### Q4. INSERT 直後の id 取得、推奨パターンは?

今回は `getBy{naturalKey}` で再 SELECT する設計にした。ポータブル + Fake 化簡単。
代替案:
- `ExtendedPdoInterface::lastInsertId()` を Resource から直接呼ぶ
- Command interface 自体に `: int` を返す独自規約を設ける
- `INSERT ... RETURNING` を抽象化したアトリビュート

BEAR.Sunday としてはどれを推奨? あるいは「ケースバイケース、現状ベストプラクティスはなし」?

### Q5. `Resource` の `_embedded` に直接 entity オブジェクトを入れて HAL renderer は
正しくシリアライズしてくれるが、これは公式の使い方として OK か?

`#[Embed]` 属性を使わず、`$this->body['_embedded'] = ['author' => $authorEntity, ...]`
と書いた。動いているが、`#[Embed]` で取れない場合 (今回は authorId が DB fetch 後に
分かるためテンプレ展開できなかった) のフォールバックとして妥当か?

### Q6. ALPS の Choreography ID と HAL の `_links` rel は 1:1 で揃えるべきか?

`goArticle` (ALPS) → `app://self/article` (Resource URI) → `_links.self.href`?
それとも `_links.goArticle.href`? 今回は `self` / `articles` / `author` 等の HAL
慣習名を使ったが、ALPS 名と揃える方が "semantic 一貫性" としては正しい気もする。

### Q7. `bear/query-repository` の `#[Cacheable]` + `#[Refresh]` / `#[Purge]` の
組み合わせ、Write 後のキャッシュ無効化のレシピが見つけにくい。

特に「`onPost` で article を作ったら `app://self/article{?id}` と
`app://self/articles` の両方をパージしたい」みたいなケース。リソース URI の
パターン指定 (`app://self/articles*`) は使えるか?

---

## 希望 (Wishes)

### W1. Ray.MediaQuery 同梱の Fake 実装

`Ray\MediaQuery\Testing\InMemorySqlQuery` のような形で公式提供されると、
プロジェクトごとに自前実装する必要がなくなる。シードデータを JSON で食わせる
インターフェースだと使い勝手がいい。

```php
$fake = InMemorySqlQuery::fromJsonDir('var/fake/');
$fake->dispatch('list_articles', static fn ($values) => [...]);  // 上書きフック
```

### W2. `#[DbQuery]` の dispatch 規約をクラスドックブロックに書いてほしい

「return 型が array 以外なら getRow、配列なら getRowList、Pager 属性なら getPages」
というルールが現在は interceptor のコードを読まないと分からない。

`DbQuery.php` のクラスコメントに数行で書いてあれば、Fake 実装で迷わずに済む。

### W3. BDR パターンの canonical サンプルプロジェクト

`Ray.MediaQuery/BDR_PATTERN-ja.md` は概念説明としては優秀だが、実プロジェクト
レイアウトに落とし込んだサンプルが欲しい。今回の BEAR.Cms がその一例になれば
嬉しい (なれていない部分も多いが)。

### W4. `CLI Tutorial` の「次の一歩」

`tutorial.html` → `tutorial2.html` → `tutorial3.html` (CLI) は良かった。
ここから「ALPS profile を書いて semantic-ex で schema を作る」までを繋ぐ
チュートリアルが、今のエコシステムの全貌を俯瞰する経路として欲しい。

### W5. Doctrine Migrations 統合のスケルトン

`bear/skeleton` 直後に `composer require doctrine/migrations doctrine/dbal` して
`migrations.php` / `migrations-db.php` / `var/db/migrations/` を生やす作業は
定型なので、`bear/skeleton-with-migrations` か、setup スクリプトのオプションで
生成できると嬉しい。

### W6. `Injector::getInstance()` が context に対する diagnostics を返すモード

DI の binding がどう解決されたかを context ごとに dump できるコマンド。
特に `fake-` 系のオーバーライドが効いているかを確認するのに今は手で
`var/tmp/{ctx}/di/*.php` を読んでいる。`composer di:dump fake-hal-api-app` 等が
あると新規参入者の謎が減る。

---

## 要望 (Requests)

### R1. `DbQueryInterceptor` の動作を `Annotation/DbQuery.php` のドックブロックに反映

具体的には:

```php
/**
 * Marks a method as a SQL query.
 *
 * Dispatch:
 * - return type is `array`           → SqlQueryInterface::getRowList()
 * - return type is anything else     → SqlQueryInterface::getRow()
 * - method has #[Pager]              → SqlQueryInterface::getPages()
 *
 * Note: SqlQueryInterface::exec() is NOT invoked by the interceptor.
 *       Write SQL (INSERT/UPDATE/DELETE) is dispatched through getRow()
 *       and the implementation is expected to detect non-SELECT
 *       statements and return null/[]. Custom SqlQueryInterface
 *       implementations (e.g. for testing) must follow this contract.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class DbQuery
```

これだけで Fake 実装の躓きは消える。

### R2. `var/tmp/{ctx}/di/` のキャッシュをデフォルトで dev では off に

または、Module の構成変更を検出して自動 invalidate。今回 FakeModule を追加した
直後にキャッシュが残っていて FakeSqlQuery が bind されない事象でしばらく悩んだ。

### R3. `composer create-project bear/skeleton` がディレクトリ非空でも動く

中身を保持したまま skeleton を被せられると、`.claude/`, `.cursorrules`,
`.envrc` 等のローカルファイルを破壊しない。`--keep-existing` オプションでも可。

### R4. ALPS skill のプロンプトで「`tag` はスペース区切り文字列」の例を強調

`alps-skills:alps` のドキュメント中に `"tag": ["a", "b"]` の例があり、これに従って
書くと E011 で落ちる。最新仕様 (`asd 2.0.0-alpha.2`) では `"tag": "a b"` 一択。
スキル側ドキュメントの修正で多くの初動エラーが消える。

---

## メタな要望

### M1. このドキュメント自体への返事は不要

質問の体裁を取ってはいるが、リアルタイムでの応答を期待しているわけではない。
何かの拍子に目を通したときの参考に。

### M2. このプロジェクトを「BEAR.Sunday の参照実装の一つ」として
位置づけるかどうかの判断は委ねる

そう位置づけたい場合は:
- BEAR.Cms を bearsunday/ org に持っていく
- README に「これは reference implementation です」と明示
- マニュアルから link する

そうでなければ「個人実験」扱いで全く問題ない。

---

## ひとこと

長年 BEAR.Sunday を維持しながら ALPS / Ray.MediaQuery / Malt / BEAR.Skills と
いったエコシステム周辺を厚くしてきた仕事は、PHP 業界としては圧倒的に独特の
ポジションだと思う。今回のセッションで BDR + ALPS + semantic-ex を一気通貫で
回せたのは、その積み重ねがあるから。

質問・要望は多いが、それは「使い込めるだけの厚みがある」ことの裏返しでもある。
