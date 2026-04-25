# BEAR.Sunday 評論 — 1日触ってみた洞察

BEAR.Cms 構築を通じて BEAR.Sunday + Ray.* + ALPS + semantic-ex を一気通貫で
使った後の評論。賛美でも批判でもなく、設計の癖と帰結を観察するためのメモ。

---

## 1. このフレームワークの本質的な賭け

PHP のメジャーフレームワーク (Laravel, Symfony) と BEAR.Sunday を比べると、
表層の API が違うのではなく **「何を1級市民にするか」が違う**。

| フレームワーク | 1級市民             |
|----------------|---------------------|
| Laravel        | 開発者体験 (DX)     |
| Symfony        | コンポーネント分離  |
| BEAR.Sunday    | **リソース** (URI)  |

Laravel が「コードを書く快適さ」を最大化するのに対し、BEAR.Sunday は
**「URI で表現される状態空間」を最大化**する。コードはその空間を実現する
手段に過ぎない。これは ROA (Resource-Oriented Architecture) の純粋形であり、
PHP 圏では類例がない。

この賭けが成功する条件は「設計時点で URI 空間が安定して描ける」こと。CMS の
ように articles / categories / tags / authors / media と粒度が明らかに揃う
ドメインなら強い。一方、トランザクションスクリプト的に書きたい計算重ループや、
URI として固定しにくいワークフローでは、この賭けの恩恵が薄れる。

**洞察:** BEAR.Sunday は「URI で語れるか」をドメインモデリングの試金石にしている。
それに耐えるドメインなら極めて強力、耐えないドメインなら他のフレームワークの方が
楽だろう。

---

## 2. ALPS との結婚 — semantic-first の真の意味

ALPS profile を最初に書く方式は、PHP 圏では BEAR.Sunday + alps-asd ツールチェーン
だけが本気で実践している。これがどう効くかを今回実感した。

**従来:** 開発者の頭の中 → コード → ドキュメント (時々忘れる)
**ALPS:** ALPS profile (一次情報) → コード / 図 / mock / OpenAPI / GraphQL / SQL DDL
                                  ↑ すべて生成可能

これは単なる「ドキュメント先行」ではない。ALPS は **意味の正規化された source of truth**
であり、そこから schema や mock を `alps-to-*` で派生させられる。私たちが今回
`alps-skills:alps-to-jsonschema`, `alps-skills:alps-to-sql` を呼んだ通り。

さらに `semantic-ex` は ALPS に対して **「制約をデータから発見する」** 層を加える。
- 通常: 「VARCHAR(255) でいいや」
- semantic-ex: 「実データ50件を観察すると最長61文字、なら maxLength=100 で十分」

これは単なる schema 自動生成ではなく、**意思決定の根拠をデータに移譲する** 思想。
LLM 時代に特に光る。ALPS profile を投げ込むと realistic な fake が生成され、
そこから schema が落ちてくる、という流れは「人間の思い込み」が介在する余地が
ない (代わりに「fake の質」が問われるが、これは検証可能)。

**洞察:** BEAR.Sunday は実は "PHP framework" というより "ALPS で記述された
状態空間を実行するランタイム" と呼んだ方が正確かもしれない。フレームワーク本体
(Resource / DI / AOP) よりも ALPS とその tooling 側に重心がある。

---

## 3. Ray.* エコシステムの構造 — ばらされ方が極端

Ray.Di (DI), Ray.Aop (AOP), Ray.MediaQuery (SQL), Ray.InputQuery (Input),
Ray.AuraSqlModule (PDO), Ray.QueryRepository (cache), Ray.Auth0Module (auth)…

これは Symfony Bundle 的な意味でのコンポーネント化を遥かに超えていて、
**個々のパッケージがそれぞれ完結したライブラリ** として独立している。
BEAR.Sunday なしでも使える設計。

メリット:
- 各層を独立にアップデートできる
- 用途に応じて pick & choose できる
- アイデアの実験単位が小さい (新しい Ray.* を作って試せる)

デメリット:
- どのパッケージがどう連携するかの全体像が掴みにくい
- デフォルトの組み合わせが「正解」とは限らず、初学者は迷う
- ドキュメントが分散する (各 README は深いが横断ガイドが薄い)

**洞察:** これは UNIX 哲学 (do one thing well) を PHP に持ち込んだ最も成功した
試み。ただし「哲学」を実装に通すと「全体像のドキュメント化」が後回しになりがち
で、BEAR.Sunday もそこに苦しんでいる印象。

---

## 4. AOP と attributes — 過小評価されているスキル

`#[DbQuery]`, `#[Cacheable]`, `#[Embed]`, `#[Link]`, `#[Inject]`, `#[Pager]` …
全部 method-level の attribute。Ray.Aop が裏で proxy class を生成して
interceptor を挟む。

これは Java の Spring 風だが PHP 圏では珍しい。Laravel/Symfony は基本的に
「明示的な call」で動き、attribute は metadata 用途が多い。BEAR.Sunday は
attribute を **動作変更の primary mechanism** にしている。

利点:
- ボイラープレートが消える (cache 設定が1行)
- ドメインロジックと cross-cutting concern (cache, transaction, logging) が分離
- テストで interceptor だけ差し替えできる

代償:
- IDE で「このメソッド呼ぶと裏で何が起きるか」が見えにくい
- Stack trace が proxy で汚れる
- Ray.Di の binding error は本当に追いにくい (経験談)

**洞察:** AOP は強力だが「Magic」と紙一重。BEAR.Sunday の AOP 利用は明示的かつ
attribute-based なのでマジックには倒れていないが、慣れない人には breaking point。
ここがフレームワーク採用判断の分岐点になることが多そう。

---

## 5. 設計の癖 — 観察された緊張点

### 5.1 "ResourceObject = 状態" vs "Resource = 振る舞い"

`ResourceObject` クラスは **インスタンスがレスポンス状態を持つ** (body, code,
headers が public プロパティ)。これは ROA の純粋形だが、PHP の return-value-style
と微妙に衝突する: `return $this;` のチェーンを書きながら `$this->body = ...` で
mutate する感じ。

慣れるが、最初は「state を持たせるべきか」「pure 関数で返すべきか」の判断で
迷う。`ResourceInterface::get()` が `ResourceObject` を返すのも、その流儀。

### 5.2 Module composition by string keyword

`fake-hal-api-app` のようなコンテキスト文字列を keyword に分解 (`fake`, `hal`,
`api`, `app`) して、各 keyword に対応する `{Keyword}Module.php` を解決する。

エレガントだが脆い:
- typo すると silent miss (FakeModule のはずが Fakkemodule で読まれない)
- 順序の意味が暗黙 (test- が fake- より前か後か)
- 新規参入者は「なぜそのモジュールが選ばれたか」を追跡しにくい

代案として PHP の `enum` を使う方法もありそう (`Context::FakeHalApiApp`) が、
それは BEAR の文化と合わない感もある。

### 5.3 PDO への深い依存

`Ray.MediaQuery::SqlQuery`, `Pages` (Pagerfanta), `ExtendedPdo` は PDO 前提で
組まれている。Fake を作るときに「PDOStatement や Pagerfanta を fake する」
コストが発生する。

実用上は PDO で困らないが、「データソースが PDO じゃない」(Elasticsearch, REST API,
gRPC) ときに同じ `#[DbQuery]` パターンを使うのは難しい。`ray/media-query-web`
のような web 版は別途あるが、まだ若い。

**洞察:** ROA は永続化層を抽象化する pressure を本来かけるはずだが、Ray.MediaQuery は
「SQL を使う」というところで pragmatically lock-in している。これは賢明な選択
(over-abstract すると性能と可読性が落ちる) だが、移植性の天井になる。

### 5.4 Read と Write の dispatch が同じ穴を通る

これは前述。`#[DbQuery]` の戻り型で getRow / getRowList を分け、INSERT/UPDATE/
DELETE もそこを通す。エレガントだが Fake 実装者には罠。

代案: `#[Query]` (read) と `#[Command]` (write) で attribute を分け、interceptor
も分ける。BDR の B/D/R に対応した命名にもなる。今からだと breaking change。

---

## 6. 開発者体験 (DX) の二面性

### よかった所

- ALPS → Fake → schema → entity → resource → SQL の **段階的解像度上昇**
  プロセスは PHP 業界では他に類を見ない。実際このプロジェクトはこの順で
  作って一度も後戻りしていない。
- `composer create-project bear/skeleton` 一発で動く starter
- リソーステストが `app://self/foo` を直接叩ける (HTTP モック不要)
- AppLog renderer が dev 時に親切 (HAL+JSON だが human readable)

### きつかった所

- 初手で「context は何種類書くべき?」「Module は何個に分けるべき?」の
  判断が手探り
- `#[DbQuery]` の戻り型による dispatch ルールがソースを読まないと不明
- DI binding error のメッセージが「何が足りないか」を直接言わない
  (Reflection ベースなので仕方ない側面はある)
- `var/tmp/*/di/` のキャッシュ消し忘れで binding が反映されない事故が起きやすい
- Pagerfanta + PDO に依存しているため `#[Pager]` の Fake が作りにくい

---

## 7. 思想的な観察

BEAR.Sunday は **「正しさ」を「快適さ」より優先するフレームワーク**。これは
Laravel の対極にある選択で、両立しないトレードオフを抱えている。

例:
- Laravel: 「ユーザーモデルを 5 分で作って動かしたい」を最優先
- BEAR.Sunday: 「URI と意味と状態の整合性を最優先、5 分で動かしたい目標は二番目」

この優先順位は「長く保守する」「複数チームで触る」「外部公開 API になる」
ケースで効いてくる。「最速で MVP 出してピボット」フェーズだと窮屈。

ALPS と semantic-ex の組み合わせは **AI 時代に特に光る**。LLM はコードを書く
だけでなく「データから制約を見つける」「URI 空間を提案する」が得意。BEAR.Sunday は
人間-LLM 協働で最も自然に回るフレームワークの一つだと思う (今回のセッション自体が
証拠)。

---

## 8. 弱点とリスク

### 8.1 採用障壁が高い

リソース指向、ALPS、AOP、DI コンテナ、context 解決、これら全部を最初に飲み込む
必要がある。Laravel は「ルートに closure」から始められるが、BEAR.Sunday は
最初から ResourceObject を理解しないといけない。

### 8.2 コミュニティサイズ

PHP 圏では小さい。商業案件で「BEAR.Sunday できる人」を探すのは難しい。これは
フレームワークの問題というより「ニッチ高品質ツールの宿命」。

### 8.3 ドキュメントの分散と発見性

各 Ray.* パッケージの README は深い。マニュアルも整備されている。しかし
**「これらを組み合わせて X するときの推奨パターン」** のようなクックブックが薄い。
今回のセッションで `BDR_PATTERN-ja.md` を見つけたとき「これがあったのか」と
驚いたが、トップマニュアルからの動線が弱い。

### 8.4 LTS とバージョニング戦略

`bear/sunday ^1.6`, `bear/package ^1.14`, `ray/di ^2.19` … セマンティック
バージョニングは守られているが、組み合わせの「動く構成」が時間で変わる。
`bear/skeleton` を pin することで現状はカバーされているが、3 年後にこの
プロジェクトを再現するのは多分しんどい。

---

## 9. 強みの再確認

弱点を並べた後で改めて言うと、BEAR.Sunday の強みは:

1. **概念的な美しさが実装に通っている** (URI = state、AOP = cross-cutting、
   Resource = boundary)
2. **PHP 圏では随一の type-safety と testability** (typed properties, readonly,
   ResourceObject test, Fake 差し替え)
3. **ALPS 統合が他に類を見ない** — semantic-first を本気で実装している唯一の
   PHP framework
4. **エコシステムの内在的な一貫性** — Ray.* も koriym/* も同じ思想で書かれて
   いるのでパターンが転用しやすい
5. **小さくキープしている** — Laravel のように何でも入れない。Resource / DI /
   AOP / Renderer 程度に絞ってある

---

## 10. もしこれから採用判断するなら

### 採用すべきケース
- API サーバー (HAL+JSON が活きる)
- 長期保守する (5 年スパン以上の) 大型プロジェクト
- 複数の view (web / cli / batch) を同じ resource で支えたい
- ALPS を使った semantic-first 設計が組織で受け入れられる
- 静的解析と type 安全性に投資する文化がある

### 採用しない方が良いケース
- 短期 MVP / プロトタイプ
- チームに PHP 経験者しかおらず学習コスト予算がない
- ユーザー UI が中心 (Laravel + Inertia や Symfony + Twig の方が DX 良い)
- フロントエンドと密結合の SSR (Next.js などとセットで使う方が速い)

---

## 11. 結論的な印象

**「BEAR.Sunday は『正しさへの執念』で書かれた、PHP 圏で最も思想的に
首尾一貫したフレームワーク」**

それは技術的優位性と同時に商業的限界でもある。Laravel は妥協で広がり、
BEAR.Sunday は妥協しないことで深まった。両方が共存することは健全。

このフレームワークが LLM 時代にますます光る理由は、**コードよりもセマンティクス
(ALPS profile, JSON schema, URI 空間) を中核に置いているから**。LLM はテキスト
で記述された semantic を読み書きできる。コード生成より「ALPS から fake を作り
schema を導く」フローを LLM が回す方が、結果のクオリティが安定する (今回実証された)。

5 年後の PHP フレームワーク landscape を予想すると、Laravel は変わらず首位だが、
BEAR.Sunday は **「LLM ネイティブな PHP 開発の reference」** として独自の地位を
強める可能性がある。今回のセッションはその予兆としても読める。
