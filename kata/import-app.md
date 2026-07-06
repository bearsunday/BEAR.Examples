# `import-app`

**ImportAppModuleで他アプリのResourceを呼ぶ** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** ImportApp, ImportAppModule, multi-app, composer package, cross-app resource, System Boundary, アプリ間連携, 他アプリ呼び出し, マルチアプリ
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/import.html
- **Use when:** composer installした別アプリのResourceを、自アプリから `app://<host>/...` で呼びたい。

## 例

### Importされる側

普通のBEAR.Sundayアプリでよい。最小構成はResourceと `AppModule` だけ:

```php
class Status extends ResourceObject
{
    public function onGet(): static
    {
        $this->body = [
            'app' => 'imported-catalog',
            'purpose' => 'BEAR.Sunday ImportAppModule companion example',
        ];

        return $this;
    }
}
```

```php
final class AppModule extends AbstractAppModule
{
    protected function configure(): void
    {
        $this->install(new PackageModule());
    }
}
```

### Importする側（wiring）

`ImportAppModule` に `ImportApp($host, $namespace, $context)` を渡してinstallする:

```php
$this->install(new ImportAppModule([
    new ImportApp('catalog', 'BEAR\Kata\Example\ImportedCatalog', 'app'),
]));
```

importするappはcomposer autoload可能であることが前提（本リポジトリは autoload-dev の PSR-4 map `"BEAR\\Kata\\Example\\ImportedCatalog\\": "examples/import/ImportedCatalog/src/"` で代替）。

### 呼び出し

importしたアプリはhost名で呼ぶ。自アプリは `app://self/...` のまま共存する:

```php
$imported = $resource->get('app://catalog/status');

$this->assertSame(200, $imported->code);
$this->assertSame('imported-catalog', $imported->body['app']);

$self = $resource->get('app://self/articles');
$this->assertSame(200, $self->code);
```

## Naming

host名は `ImportApp` の第1引数で決め、URIのauthorityになる。resource pathはimport元の `Resource/App/` 以下のclass名から決まる:

| 要素 | 値 | 導出 |
|---|---|---|
| host | `catalog` | `ImportApp('catalog', ...)` の第1引数 |
| namespace | `BEAR\Kata\Example\ImportedCatalog` | import元appのroot namespace |
| URI | `app://catalog/status` | `<namespace>\Resource\App\Status` に対応 |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `ImportAppModule` に `ImportApp($host, $namespace, $context)` を渡してinstallすると決めたか。
- [ ] 呼び出し側は `app://<host>/<resource>` でアクセスし、`#[Embed]` / `#[Link]` も使えると理解したか。
- [ ] importするappがcomposer autoload可能であることを確認したか（本リポジトリはautoload-devのPSR-4 mapで代替）。

## Source

- [`examples/import/ImportedCatalog/src/Resource/App/Status.php`](../examples/import/ImportedCatalog/src/Resource/App/Status.php)
- [`examples/import/ImportedCatalog/src/Module/AppModule.php`](../examples/import/ImportedCatalog/src/Module/AppModule.php)
- [`tests/Example/ImportAppExampleTest.php`](../tests/Example/ImportAppExampleTest.php)

## Tests

- [`tests/Example/ImportAppExampleTest.php`](../tests/Example/ImportAppExampleTest.php)

## Key points

`ImportAppModule` で他アプリをimport。host名経由でresource呼び出し。`#[Embed]` / `#[Link]` も使用可能。マイクロサービス化せずともcomposer経由でアプリ間連携できる。他framework/CMS側からは `BEAR\Package\Injector::getInstance($appName, $context, $appDir)` で対象appのresource clientを直接取得できる。

## Do not

- 環境変数はglobalなのでapp間で衝突しないようprefixを付ける — importしても各appのenvは分離されない。

## マスター確認（After）

- [ ] 他アプリのresourceが `app://<host>/...` で呼べることを `ImportAppExampleTest.php` 相当で green。

## See also

- [`hal-link`](./hal-link.md) — importしたresourceにも使える `#[Link]` の基本形
- [`hal-embed`](./hal-embed.md) — importしたresourceを `#[Embed]` で埋め込む
- [`api-get-hal-resource`](./api-get-hal-resource.md) — 呼び出される側Resourceの基本形
- [`cli-resource`](./cli-resource.md) — 同じResourceをCLIという別の面で公開する類型
- [`tool-use-instrument`](./tool-use-instrument.md) — 同じResourceをLLMのtoolとして公開する類型
