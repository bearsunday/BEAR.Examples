# `cli-resource`

**ResourceをCLIコマンドとして公開する** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** CLI, `#[Cli]`, `#[Option]`, bear-cli-gen, article-show, article-list, bear/cli, コマンドライン, CLIコマンド化, Homebrew配布
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/cli.html
- **Use when:** 同じResource methodをHTTPだけでなくCLIからも呼びたい。

## 例

### Resource method

既存の `onGet` に `#[Cli]` と `#[Option]` を付けるだけ — method本体は変えない:

```php
#[Cli(name: 'article-show', description: 'Show an article by id', output: 'title')]
public function onGet(#[Option(shortName: 'i', description: 'Article id')] int $id): static
```

collection resource側は引数がそのままCLI optionになる:

```php
#[Cli(name: 'article-list', description: 'List articles (paginated, filterable)')]
public function onGet(
    #[Option(shortName: 'p', description: 'Page number')]
    int $page = 1,
    #[Option(shortName: 'n', description: 'Items per page (max 100)')]
    int $perPage = 20,
    #[Option(shortName: 'c', description: 'Filter by category id')]
    int|null $categoryId = null,
    #[Option(shortName: 't', description: 'Filter by tag id')]
    int|null $tagId = null,
    #[Option(shortName: 'a', description: 'Filter by author id')]
    int|null $authorId = null,
    #[Option(shortName: 's', description: 'Filter by status (draft|published)')]
    string|null $status = null,
): static
```

### 生成コマンド

`composer cli`（= `bear-cli-gen`）が `bin/cli/` にコマンドを生成する。中身は `cli-hal-api-app` contextのresource clientを呼ぶだけ:

```php
$resource = Injector::getInstance('cli-hal-api-app')->getInstance(ResourceInterface::class);
$config = new Config('app://self/article', new \ReflectionMethod(\BEAR\Kata\Resource\App\Article::class, 'onGet'));
$command = new ResourceCommand($config, $resource);
$result = $command($argv);

echo $result->message . PHP_EOL;
exit($result->exitCode);
```

### 生成と実行

```bash
composer cli                              # = ./vendor/bin/bear-cli-gen 'BEAR\Kata'
bin/cli/article-show --help               # Usage / Options を表示
bin/cli/article-show -i 1 --format json   # app://self/article?id=1 のGETと同じbody
```

## Naming

CLI名は `#[Cli(name:)]` で指定し、生成物は `bin/cli/<name>` に置かれる。item ↔ collection の語彙対（`Article` ↔ `Articles`、`item` ↔ `list`）をCLI名にも揃える:

| Resource | CLI name | 生成物 |
|---|---|---|
| `Article::onGet`（item） | `article-show` | `bin/cli/article-show` |
| `Articles::onGet`（collection） | `article-list` | `bin/cli/article-list` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 同じResource methodを再利用し、CLI用の別serviceを重複実装しないと決めたか。
- [ ] method に `#[Cli]` / `#[Option]` を付け、`composer cli` で生成すると理解したか。

## Source

- [`src/Resource/App/Article.php::onGet()`](../src/Resource/App/Article.php)
- [`src/Resource/App/Articles.php::onGet()`](../src/Resource/App/Articles.php)
- [`bin/cli/article-show`](../bin/cli/article-show)
- [`bin/cli/article-list`](../bin/cli/article-list)

## Tests

- なし（専用テストは無い。生成コマンドの動作は手動確認）

## Key points

Resource methodに `#[Cli]` と `#[Option]` を付け、`composer cli` で生成する。生成コマンドは `cli-hal-api-app` contextのresource clientを呼ぶだけ（重複実装ゼロ）。default出力は `output:` で指定したbody fieldのみで、`--format json` でAPIと同じfull JSON。errorはexit codeで表す — HTTP statusのmapping（0=success / 1=client error / 2=server error）。GitHub repository設定時はHomebrew formulaも生成される。

## Do not

- CLI用に別のapplication serviceを重複実装しない。既存のResource methodを `#[Cli]` で公開すればCLIとHTTPで同じロジックを共有できる。

## マスター確認（After）

- [ ] CLI が既存Resource methodを呼び、ロジックの重複実装が無い。
- [ ] `bin/cli/article-show --help` がUsage/Optionsを表示し、`bin/cli/article-show -i 1 --format json` が `app://self/article?id=1` のGETと同じbodyを返す。

## See also

- [`api-get-hal-resource`](./api-get-hal-resource.md) — CLIが呼び出すGET Resourceの基本形
- [`db-read-one-entity`](./db-read-one-entity.md) — `article-show` の裏にある主キー1件読み
- [`db-read-list-pager`](./db-read-list-pager.md) — `article-list` の裏にあるページング一覧
- [`error-status-mapping`](./error-status-mapping.md) — HTTP statusへの写像（CLIではexit codeに対応）
- [`import-app`](./import-app.md) — 別アプリからResourceを再利用する、もう一つの「同じResource・別の面」
- [`tool-use-instrument`](./tool-use-instrument.md) — ResourceをLLMのtoolとして公開する類型
