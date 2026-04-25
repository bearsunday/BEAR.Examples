# 実験で検証した事実

評論を書く際に憶測で書いてしまった項目について、実際にコードを動かして確認した
内容を記録する。これは評論ではなく **観察の記録**。

## 1. Ray.MediaQuery のバインディングフロー

### 主張していたこと (憶測)

> `MediaQueryBaseModule` が Query interface に `toNull()` し、interceptor が
> 介入する。method 呼び出しは proxy → DbQueryInterceptor → SqlQuery に流れる。

### 実際に確認したこと

**(a)** `vendor/ray/media-query/src/MediaQueryBaseModule.php:35-37`:
```php
foreach ($this->queries->classes as $class) {
    $this->bind($class)->toNull();
}
```

**(b)** `vendor/ray/di/src/di/Bind.php:148-159` (`toNull()`):
```php
public function toNull(): self {
    assert(interface_exists($this->interface));
    $this->bound = new NullObjectDependency($this->interface);
    ...
}
```

**(c)** 生成される実体ファイル `var/tmp/test-hal-api-app/di/MyVendor_Cms_Query_ArticleQueryInterface82f19566Null.php`:
```php
class ArticleQueryInterface82f19566Null implements \MyVendor\Cms\Query\ArticleQueryInterface
{
    #[DbQuery('get_article', type: 'row')]
    public function get(int $id): Article|null {}        // <- 空ボディ

    #[DbQuery('get_article_by_slug', type: 'row')]
    public function getBySlug(string $slug): Article|null {}  // <- 空ボディ
    ...
}
```

→ `toNull()` は **「interface を実装する空メソッド本体のクラス」を生成** する。

**(d)** さらに interceptor 適用後の proxy クラス
`var/tmp/test-hal-api-app/di/MyVendor_Cms_Query_ArticleQueryInterface82f19566Null_3458897808.php`:
```php
class ArticleQueryInterface82f19566Null_3458897808
    extends ArticleQueryInterface82f19566Null
    implements \MyVendor\Cms\Query\ArticleQueryInterface, \Ray\Aop\WeavedInterface
{
    use \Ray\Aop\InterceptTrait;

    #[\Ray\MediaQuery\Annotation\DbQuery('get_article', type: 'row')]
    public function get(int $id): null|\MyVendor\Cms\Entity\Article {
        return $this->_intercept(__FUNCTION__, func_get_args());
    }
    ...
}
```

→ proxy が Null クラスを extends し、各メソッドを `_intercept()` でラップする。

**(e)** `_intercept` → `DbQueryInterceptor::invoke()` →
`#[DbQuery]` 属性を読む →
- 戻り型と type で getRow/getRowList を選択 (`SqlQuery.php:perform()`)
- INSERT/UPDATE/DELETE は SELECT 検出ロジックで結果 `[]` 返却
- 結果は `SqlQueryInterface::getRow()` の戻り値として interceptor からそのまま返る

### 結論

私が `framework-critique.md` で書いた「AOP 越しに何が起きるか見えにくい」は
**事実と逆**。

- 生成される proxy クラスは `var/tmp/{ctx}/di/` に物理ファイルとして残る
- そこを開けば、各メソッドが何を呼ぶか、どんな attribute が付いているか、
  すべてプレーン PHP で読める
- IDE は元の interface に navigate できる; proxy は autoload で透過される

Laravel の `User::find()` (Facade + `__callStatic`) と比べて遥かに透明。

---

## 2. Ray.Di の binding error メッセージ

### 主張していたこと (憶測)

> DI binding error のメッセージが「何が足りないか」を直接言わない
> (Reflection ベースなので仕方ない側面はある)

### 実際に確認したこと

3 段の依存チェーンを意図的に壊して、Ray.Di が出すエラーを観察:

```
Resource(BrokenChain) → ServiceA → ServiceB → UnboundServiceInterface (未バインド)
```

**`getMessage()` の出力 (1 行):**
```
'MyVendor\Cms\Test\ServiceA-' in /path/src/Resource/App/BrokenChain.php:12 ($serviceA)
```

**`__toString()` の出力 (チェーン全展開):**
```
exception 'Ray\Di\Exception\Unbound' with message ''MyVendor\Cms\Test\UnboundServiceInterface-''
- 'MyVendor\Cms\Test\UnboundServiceInterface-' in /path/src/Test/ServiceB.php:9 ($unbound)
- 'MyVendor\Cms\Test\ServiceB-' in /path/src/Test/ServiceA.php:9 ($serviceB)
- 'MyVendor\Cms\Test\ServiceA-' in /path/src/Resource/App/BrokenChain.php:12 ($serviceA)
```

各行に:
- 不足している型名 (および name 修飾子)
- 要求された PHP ファイル:行番号
- 要求された **コンストラクタ引数名**

が含まれる。チェーンは PHP の `Throwable::getPrevious()` 連鎖で繋がっており、
`Ray\Di\Exception\Unbound::__toString()` (Unbound.php:25-71) が連鎖を順に formatter に掛ける。

### 結論

**私の「直接言わない」批判は完全に事実誤認。**

PHP 圏で見たことのある中で最も親切な DI binding error の部類。エラーから直接:
- 何が不足しているか
- なぜ必要だったか (要求元)
- 完全な依存チェーン

がわかる。`__toString()` で出力するか、Whoops 等の error renderer に渡せば
チェーン全部が表示される。

---

## 3. 「コンテキスト keyword の typo」

### 主張していたこと (憶測)

> `fake-hal-api-app` の typo は silent miss する

### 実際に確認したこと

`vendor/bear/package/src/Module.php:41-50`:
```php
private function installContextModule(...) {
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

`fak-hal-api-app` を渡すと:
1. `MyVendor\Cms\Module\FakModule` を `class_exists` 判定 → false
2. `BEAR\Package\Context\FakModule` にフォールバック → false
3. `is_a(...)` が false → **`InvalidContextException("fak")` を投げる**

例外メッセージには typo した keyword (`"fak"`) が含まれる。

### 結論

silent miss は **起きない**。明示的な例外が投げられ、何が typo かもわかる。

---

## まとめ

`framework-critique.md` で「弱点」として並べた 3 つの DI / AOP 関連の批判は、
**実装を読むかコードを動かせば即座に反証される**。私はそれをせず、一般的な
「DI コンテナは難しい」「AOP は magic」のような業界慣用句を BEAR に投影した。

評論を書く前にやるべきだったのは:

1. `vendor/ray/di/src/di/Exception/Unbound.php` を読む (71 行)
2. `vendor/bear/package/src/Module.php` を読む (60 行)
3. `var/tmp/{ctx}/di/` の生成ファイルを開く
4. binding error を意図的に発生させて観察する

合計 30 分の作業で `framework-critique.md` の 3 つの誤りは防げた。

このドキュメントは批評ではなく **検証の記録** として残す。今後別の評価をする
ときの自戒のため。
