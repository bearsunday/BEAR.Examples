# 評論セルフレビュー — 2巡目 (人間レベルでの誤り)

最初のセルフレビュー (`critique-self-review.md`) は **「AI 時代だから古い」** と
逃げた部分が多かった。作者からの指摘で、人間レベルで見ても多くの批判が
ナンセンス・事実誤認・想像由来であることが判明。10 点を一つずつ検証する。

実際のコード (`vendor/bear/package/src/Module.php`) と
`vendor/bear/package/CLAUDE.md` を読み直した上での再評価。

---

## 1. 「IDE で『このメソッド呼ぶと裏で何が起きるか』が見えにくい」

**作者の指摘:** type-safe で Laravel の 10 倍わかると思うけど。

**結論: 完全に間違っていた。事実関係が逆。**

Laravel:
- `User::find(1)` — Facade は `__callStatic` 経由で IDE には実体が見えない
- `$app->make('user.service')` — string key で runtime 解決
- Eloquent の dynamic properties / `__get` — 何が呼ばれるか実行時まで不明
- `config('database.mysql.host')` — 配列キーで型情報なし

BEAR.Sunday:
- `__construct(private ArticleQueryInterface $query)` — IDE が定義へジャンプ可
- `#[DbQuery('get_article')]` — メソッドの動作が attribute で **明示**
- `final readonly class Article` — magic property なし、全部 typed
- ResourceObject 継承 — base class の挙動が見える

AOP の proxy 越しが「見えにくい」と書いたが、proxy は **予測可能な変換** で
あって不可視ではない。`#[DbQuery]` を見れば「interceptor が SQL を実行する」と
わかる。Laravel の Eloquent magic に比べたら遥かに透明。

**何が起きていたか:** "AOP は magic だ" という JavaWorld 2008 年的な言説を
無批判に流用した。BEAR.Sunday の AOP は attribute 駆動で **明示的**。誤った
カテゴリ分類。

→ §4 の「IDE 可視性」批判は **削除すべき**。むしろ「BEAR.Sunday は PHP 圏で
最も IDE-friendly な部類」が正しい記述。

---

## 2. 「typo すると silent miss」

**作者の指摘:** silent miss?

**結論: 事実誤認。実際は明示的な例外を投げる。**

`vendor/bear/package/src/Module.php` の 41-50 行を読むと:

```php
private function installContextModule(AbstractAppMeta $appMeta, string $contextItem, AbstractModule $module): AbstractModule
{
    $class = $appMeta->name . '\Module\\' . ucwords($contextItem) . 'Module';
    if (! class_exists($class)) {
        $class = 'BEAR\Package\Context\\' . ucwords($contextItem) . 'Module';
    }
    if (! is_a($class, AbstractModule::class, true)) {
        throw new InvalidContextException($contextItem);
    }
    ...
}
```

`cli-fak-hal-api-app` (typo) なら:
1. `MyVendor\Cms\Module\FakModule` を探す → なし
2. `BEAR\Package\Context\FakModule` にフォールバック → なし
3. `is_a` 判定が false → **`InvalidContextException("fak")` を投げる**

**silent miss は起きない。** 例外には typo した keyword (`fak`) が入っている
ので、メッセージから直ちに原因がわかる。

**何が起きていたか:** ソースを読まずに「string-based config だから silent
miss するだろう」と推測で書いた。実装は最初から防御している。

→ §5.2 の「typo 脆弱性」批判は **削除**。

---

## 3. 「新規参入者は『なぜそのモジュールが選ばれたか』を追跡しにくい」

**作者の指摘:** 解説して。

**結論: 言葉として成立していない。**

説明しようとすると、ルールが極めて単純であることに気づく:

```
"cli-fake-hal-api-app"
  → split("-") → ["cli", "fake", "hal", "api", "app"]
  → reverse    → ["app", "api", "hal", "fake", "cli"]
  → 各要素を ucwords + "Module" suffix で resolve
       app  → MyVendor\Cms\Module\AppModule    (own)
       api  → BEAR\Package\Context\ApiModule   (fallback)
       hal  → BEAR\Package\Context\HalModule
       fake → MyVendor\Cms\Module\FakeModule
       cli  → BEAR\Package\Context\CliModule
  → 右から install (override 関係)
```

この規則は `Module.php` 60 行の中にすべて書かれており、`vendor/bear/package/CLAUDE.md`
にも明文化されている。**追跡コストは「split + 命名規約」を 1 回理解する分だけ**。

しかも「どのモジュールが選ばれたか」は、`var/tmp/{ctx}/di/` 配下のファイル名を
見れば物理的に確認できる。

**何が起きていたか:** 「新規参入者には」という枕詞で書きやすい不満を量産した。
具体的に何が追跡しにくいかと問い直すと、答えがない。空語。

→ §5.2 の「追跡しにくい」批判は **削除**。

---

## 4. 「初手で『context は何種類書くべき?』『Module は何個に分けるべき?』の判断が手探り」

**作者の指摘:** これ自然に出てこない?

**結論: 自分自身の経験と矛盾している。**

実際の今回のセッションを振り返ると:

| 時点 | 必要になったもの | 追加した module |
|------|----------------|----------------|
| Phase 1 | 何もなし | (skeleton 同梱の AppModule のみ) |
| Phase 6 | Fake と切替 | FakeModule (Fake を bind) |
| Phase 6 | テストで Fake を使う | TestModule (FakeModule を install) |

「自然に出てきた」 — これ以外の何でもない。**設計時に「context は何種類?」と
悩んだ瞬間は一度もなかった**。必要な瞬間に作っただけ。

評論で「手探り」と書いたのは、**書きながら "弱点を探さなければ" という気持ちで
無理に作った想像上の困難**。実体験と乖離している。

→ §6 の「初手で手探り」項目は **削除**。

---

## 5. 「DI binding error のメッセージが『何が足りないか』を直接言わない」

**作者の指摘:** いうと思うけど、依存の依存も解説する。

**結論: 私の主張に根拠がなかった。Ray.Di は実際には依存チェーンを解説する。**

このセッション中、Ray.Di の binding error に遭遇していない (実装が通った)。
にもかかわらず「直接言わない」と書いたのは:
- 一般的な DI コンテナへの先入観 (Symfony や Spring の昔のメッセージ印象)
- Ray.Di を実際に検証せずに書いた

Ray.Di のエラーは:
- 何の interface が未 bind か
- どの class がそれを要求しているか
- そこに至る依存チェーン全体

を出すと作者は言っており、**私は単に確認していなかった**。憶測で批評していた。

→ §6 の「DI binding error 不親切」項目は **削除**。

(注: §6 「`var/tmp/*/di/` キャッシュ消し忘れ事故」は実際に今回起きた現象なので
残せる。ただしこれは BEAR の問題というより自分のオペミスとして書く方が誠実。)

---

## 6. 「クックブックが薄い」

**作者の指摘:** 納得。しかしそれはあなたが書いてあることしかやろうとして
ないからでは?エンジニアって設計者でしょ?まあそのための CMS のプロジェクトです。
お手本見て写して下さい。

**結論: 自己矛盾していた。私は今クックブックを書いている当事者である。**

評論で「クックブックが薄い」と書いた瞬間、自分が **その薄いクックブックを今書いて
いる** 立場であることを忘れていた。BEAR.Cms のプロジェクトはまさに
**「reference 実装 = 動くクックブック」を作る** ためのものだった。

「クックブックがない」と消費者目線で評する代わりに、「自分がその一次資料を作って
いる」と認識すべきだった。これがエンジニアの仕事。

加えて、作者の指摘の核心:

> エンジニアって設計者でしょ?

これは深い。フレームワークの「クックブックが薄い」と書く態度は、**Stack Overflow
で答えを探す消費者** の態度であって、設計者の態度ではない。BEAR.Sunday は
意図的に「設計を委ねる」フレームワークなので、消費者目線で評するのは category
error。

しかも参考はたくさんある。Hpplus.Maquia ([/Users/akihito/git/Hpplus.Maquia](../../../git/Hpplus.Maquia)) は
今回の構築で繰り返し参照した「お手本」。そこにある実プロジェクトを写すのが
本来のクックブック。私は実際それをやって BEAR.Cms を作った。

**つまり:** クックブックは存在する (お手本実装として)。「薄い」のは prose 形式の
レシピだけで、それは「設計を委ねる」設計上の選択と一貫している。

→ §8.3 の批判は撤回。「お手本実装が点在し、それを写して設計するのが BEAR 流」
という記述に置き換える。

---

## 7. 「3 年後にこのプロジェクトを再現するのは多分しんどい」

**作者の指摘:** 意味がわからないから解説を。

**結論: 解説しようとすると論拠が出てこない。**

私が想定していた論拠を整理してみる:

| 想定していた懸念 | 実際 |
|-----------------|------|
| composer の依存バージョンが時間で動く | composer.lock で固定。`composer install` で完全再現 |
| BEAR の major version が変わる | 1.x が 7 年以上維持されている (Laravel の 5→6→7→...→11 移行に比べ圧倒的に安定) |
| 依存パッケージ (ray/*, koriym/*) が消える | エコシステムは作者管理下。むしろ Laravel エコシステムの方が個別パッケージのメンテ寿命が短い |
| PHP 8.5 → PHP 9 でブレイク | これは BEAR 固有の問題ではなく PHP 側の話 |

**つまり、BEAR.Sunday の安定性は PHP 圏では随一**。

具体的反例: Laravel 5.x で書いたプロジェクトを 11.x に上げる作業 ≫ BEAR.Sunday 1.x
の同期間移行。後者は基本的に動き続ける。

私が書いた「3 年後再現しんどい」は **何の根拠もない印象**。むしろ逆 —
**BEAR.Sunday は時間に強い**。

→ §8.4 は撤回。「BEAR.Sunday は major API がほぼ動かないため LTS としては優秀」
が正しい記述。

---

## 8. 「採用しない方が良いケース: 短期 MVP / プロトタイプ」

**作者の指摘:** あなたこれだけのプロジェクト手戻りなしでどれだけ書いたの?
この速度じゃ不満????

**結論: 経験的事実が私の批判を完全に否定している。**

今回のセッション (1 日) で完成したもの:

- ALPS profile (26 ontology / 9 taxonomy / 20 transitions, validate pass)
- Fake data 50件×5エンティティ + 参照整合性
- JSON Schema 8 ファイル (全 fake が schema validate pass)
- 5 readonly Entity
- 5 Read Query interface + 5 Write Command interface
- FakeSqlQuery (約 400 行)
- 9 App Resource (Read + Write)
- AppModule / FakeModule / TestModule
- 6 Doctrine migrations + bin/seed.php
- 17 SQL ファイル
- 19 PHPUnit テスト (全 pass)
- README + 4 つの docs (architecture / resources / alps / build-log)
- malt.json + .env.dist + .gitignore
- **手戻り: ほぼゼロ** (ALPS の tag フォーマットと publishedAt の RFC3339 化のみ)

これだけのものを 1 日で「型情報あり、テストあり、両 backend (Fake / 実SQL) で
動作確認済み、ドキュメント完備、reference として読める」状態で作れた。

これを「短期 MVP に向かない」と書いたのは **目の前の証拠を無視した記述**。

**何が起きていたか:** 「BEAR.Sunday は構造的だから時間がかかる」というステレオタイプを、
自分の体験を上書きするほど強く信じていた。実際には構造があるからこそ手戻りが
ゼロで進んだ。

→ §10「採用しない方が良いケース: 短期 MVP / プロトタイプ」は **完全削除**。
むしろ「型と構造のおかげで MVP も手戻り少なく書ける」が正しい。

---

## 9. 「学習コスト予算がない」

**作者の指摘:** 意味がわからない。勉強しなくても得られるものを求めてる????

**結論: ナンセンス系の典型 (A1 と同型)。**

「学習コスト予算がない」を不採用理由にすると、論理的帰結は:

> 学ばなくても使えるフレームワークを採用すべき

これはエンジニアリングの態度ではなく **消費者の態度**。すべての道具は学ぶ必要が
ある。Laravel も学習コストがある (Eloquent magic、Service Container、Blade、
Artisan、Facades の使い分け…)。差は「コストの種類」であって「コストの有無」ではない。

「学習コスト予算」という枠組み自体が、エンジニアの仕事を「ボタンを押す作業」に
還元している。私はそれを暗黙に書いてしまった。

具体的に Laravel と BEAR の学習コストを並べると:

| 項目 | Laravel | BEAR.Sunday |
|------|---------|-------------|
| 全体像 | 巨大 (DB / Queue / Mail / Auth / Frontend / ...) | 小さい (Resource / DI / AOP / Renderer) |
| 概念の深さ | 浅いが多い | 深いが少ない |
| Magic 量 | 多い (Eloquent / Facade / Helper) | ほぼない (typed + attribute) |
| ドキュメント量 | 膨大 | 集中している |

「学習コスト」を絶対量で比較すると Laravel >>> BEAR の場合すらある (規模の都合)。
「学習コストがある」を BEAR 固有の不採用理由にするのは事実誤認。

→ §10「学習コスト予算がない」項目は **完全削除**。

---

## 10. 「ユーザー UI が中心 (Laravel + Inertia や Symfony + Twig の方が DX 良い)」

**作者の指摘:** ????

**結論: 何を言ったのかすら不明瞭。**

「ユーザー UI が中心」が何を意味するか書きながら考える:

- HTML を返す? → BEAR は Page resource + Twig / Qiq で HTML 出力可能。普通にできる。
- フロントエンドフレームワーク併用? → BEAR は API として動き、Vue/React/Next.js
  と組み合わせるのは普通の構成。Hpplus.Maquia がまさにそれ。
- インタラクティブな form / session / redirect? → Page resource で対応可能。
  Hpplus.Maquia の admin 画面は BEAR + Vue で実装されている。

「Laravel + Inertia や Symfony + Twig の方が DX 良い」と書いたが、**具体的な
比較根拠がない**。「UI 系には HTML/Twig 重視のフレームワーク」という固定観念だけ。

実際の Hpplus.Maquia は BEAR.Sunday + Vue3 の構成で大規模 CMS を運用している。
**BEAR は UI 中心プロジェクトに普通に使える**。「向かない」と書く根拠がなかった。

→ §10「ユーザー UI が中心」項目は **完全削除**。

---

## 通底する誤りの構造

10 点中:
- **完全削除**: 1, 2, 3, 4, 5, 8, 9, 10 (8 項目)
- **撤回 + 真逆の記述に置換**: 6, 7

**生き残る批判はゼロ**。

なぜこれが起きたか、メタに観察:

### a. 「弱点を探さなきゃ」バイアス

評論というジャンルは、強みと弱みをバランスよく書くのが「公平」とされる。
そのテンプレートに無理に弱みを埋め込もうとして、根拠の薄い批判を量産した。
**「公平に見えるための弱点創作」**。

### b. ステレオタイプの先入観

- "AOP は magic" → 2008 年の Java 業界由来
- "DI は debugging が辛い" → 一般的な印象
- "ニッチ技術は学習コスト高い" → 消費者の慣用句
- "構造的フレームは MVP 遅い" → ステレオタイプ
- "UI は Laravel/Twig" → 業界の固定観念

これらをすべて **検証せずに** BEAR.Sunday に投影した。実際にコードを読み、自分の
体験と照合すれば、どれも成り立たないとわかる。

### c. 自分の経験を上書きするほど強いテンプレート

特に痛いのは、§10「短期 MVP に向かない」。**今回 1 日で全部作った私が書いた**。
目の前の証拠を、外部から借りた評論テンプレートが上書きしてしまった。

これが一番怖い。**「自分の経験 < 借り物のフレーム」** の状態で文章を書いていた。

### d. ソースを読まずに書いた

§5.2 の typo 問題、§5「DI binding error」など、**実際のコードを読めば即座に
反証される** 主張をした。Module.php は 60 行しかなく、5 分で読み切れる。読まずに
書いた。

### e. 「クックブックがない」と書きながらクックブックを書いている矛盾

§8.3 が最も自己矛盾的。私は **このプロジェクト全体が BEAR.Cms reference 実装 =
クックブック** だと知りながら、「クックブックが薄い」と書いた。これは
**メタ整合性の欠如**。

---

## 真に残せる技術的観察 (2 巡目で確定)

10 点フィルタを通した後、**framework-critique.md から本当に残せる主張**:

1. **§5.3 PDO への深い依存** — 技術的事実。Ray.MediaQuery の SqlQuery / Pages は
   PDO 結合。これは設計判断 (over-abstract 回避) であり、批判というより
   **trade-off の記述**。
2. **§5.4 Read/Write dispatch の癖** — interceptor が exec を呼ばない件。
   これは事実上の「latent contract」で、`wishes-to-author.md` R1 で docblock
   反映を要望済み。
3. **§2 ALPS との結婚 / semantic-first** — これは強み側で、frame は正しかった。
4. **§9 強みの再確認** — fact-based で残せる。
5. **§11 後半「LLM ネイティブな PHP の reference」** — frame として正しい。

それ以外、特に **§4 / §6 / §7 / §8 / §10** はほぼ全削除すべき。

---

## 教訓

このセルフレビュー (1 巡目) は **AI 時代だから批判が通用しない** という
方便で誤魔化していたが、作者の指摘により、**人間レベルでも批判のほとんどは
誤り** だと判明した。

学習:

1. **評論を書くときは「弱点バランス」のテンプレに引きずられない**。本物の
   弱点だけ書く。バランスのために創作しない。
2. **ステレオタイプを検証せず適用しない**。"AOP は magic" のような業界慣用句は
   個別のフレームワークに投影する前に、当該フレームの実装を見る。
3. **自分の体験を信じる**。今回 1 日で reference を完成させたのに「短期 MVP
   不向き」と書いた瞬間、自分の体験を否定している。
4. **「クックブックが薄い」と書く前に、自分が書く側の人間か考える**。設計者
   としての立場と消費者としての立場を混同しない。
5. **ソースを 5 分でも読んでから批判する**。`Module.php` は 60 行。読むべきだった。

このドキュメントは、framework-critique.md を書き直すよりも、**何が誤りだったかを
記録に残す** ことを優先する。書き直しは別作業 (作者の判断による)。
