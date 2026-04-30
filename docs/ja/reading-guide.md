# コードリーディングガイド

[English](../en/reading-guide.md)

このガイドは、API 全体を覚えるためではなく、このリポジトリを
コードとして読むための入口です。Article GET は小さく、主要な層をまたぐ
ため最初の題材にします。後続のパスで書き込み、collection、context、fake
まで読みます。

## Pass 1: 正規の Read の流れ

まず Article GET を読みます。書き込み処理の関心を持ち込まずに、主要な
設計要素を確認できます。

1. `src/Resource/App/Article.php` — Resource 境界、HAL link/embed、レスポンス body の形。
2. `src/Entity/Article.php` — domain data と、配列側へ散らすべきではない表示補助。
3. `src/Query/ArticleQueryInterface.php` — `#[DbQuery]` による宣言的な read contract。
4. `var/db/sql/article_item.sql` — query interface が期待する SQL の行 shape。
5. `tests/Resource/App/ArticleTest.php` — client 側から見た振る舞いの contract。
6. `tests/Fake/FakeSqlQuery.php` — fake のディスパッチ規則。interface だけを読むより query contract が具体的に分かります。

## Pass 2: Write の流れ

次に Article の write を読みます。入力検証、command、レスポンス再構成の
つながりを確認します。

1. `src/Resource/App/Article.php::onPost()` と `onPut()` — Resource レベルの input DTO、status code、response body 構築。
2. `src/Input/ArticleCreateInput.php` と `ArticleUpdateInput.php` — Resource が command を呼ぶ前の境界正規化。
3. `src/Query/ArticleCommandInterface.php` と `src/Query/ArticleTagCommandInterface.php` — write contract。
4. `var/db/sql/article_add.sql`、`article_update.sql`、`article_tag_*.sql` — SQL の副作用と parameter 名。
5. `tests/Resource/App/ArticleTest.php` — create/update/delete の振る舞い。

## Pass 3: Resource のまとまり

Article の後は、小さな resource family を読んで同じ規約を確認します。

- `Author.php`、`Category.php`、`Tag.php` は simple item resource。
- `Articles.php`、`Categories.php`、`Tags.php` は collection resource。
- `Media.php` は upload 的な data と filename lookup。
- `Auth.php` は CRUD 形ではない action-style resource。

## Pass 4: Runtime Context

最後に composition と test support を読みます。

- `src/Module/AppModule.php`、`FakeModule.php`、`TestModule.php` は context ごとの binding。
- `tests/Fake/FakeSqlQuery.php` は in-memory MediaQuery の振る舞い。
- `tests/Fake/FakeExtendedPdoProvider.php` は raw-PDO variation test の支援。
- `bin/demo.php` と `bin/demo-variations.php` は実行できる例。

## 層ごとの見どころ

| Area | Files | Reading focus |
|---|---|---|
| Resource layer | `src/Resource/App/*` | HTTP method の形、status code、body construction、`#[JsonSchema]`、`#[Link]`、`#[Embed]`。 |
| Entity layer | `src/Entity/*` | immutable な domain data、computed field、data の近くに置くべき振る舞い。 |
| Query layer | `src/Query/*` | Read/Write split、method 名、attribute が PHP method と SQL file をどう対応させるか。 |
| SQL layer | `var/db/sql/*` | column alias、parameter 名、entity/resource が受け取る row shape。 |
| Composition | `src/Module/*` | production、fake、test の context-specific wiring。 |
| Tests and fakes | `tests/*` | executable contract。fake は便利な data を返すだけでなく、意味を保つ必要があります。 |

## 比較ガイド

トレードオフを読むときは、焦点を絞った比較ドキュメントを使います。

- [`src/Resource/App/Variations/README.md`](../../src/Resource/App/Variations/README.md)
  は Article GET variation set。
- [`src/Resource/App/Variations/README.ja.md`](../../src/Resource/App/Variations/README.ja.md)
  は日本語版。
- [`docs/conventions.md`](../conventions.md) は命名や shape のルールを確認する場所。

## 読むときのルール

[`docs/conventions.md`](../conventions.md) を現在の rulebook として扱います。
`docs/journal/*` は historical context です。判断の背景を知るには有用ですが、
新しいコードを書くときに正とする文書ではありません。
