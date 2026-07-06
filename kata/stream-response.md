# `stream-response`

**ファイルやバイナリをストリームで返す** · [← 索引に戻る](../index.md)

- **Category:** Runtime / representation
- **Status:** `showcase`
- **Aliases:** streaming, stream response, file download, binary response, BEAR.Streamer, `StreamTransferInject`, `Content-Disposition`, StreamResponder, ストリーミング, ファイルダウンロード, 大容量レスポンス
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/stream.html
- **Use when:** JSONではなく、ファイル本体や大きなレスポンスを返したい。

## 例

### Resource

正規のMedia応答はJSON形（`Media::onGet()`）。表現がファイル本体そのものである時のみ、comparison用の `Variations\MediaStream` のように `StreamTransferInject` を使い、open stream resource を `$this->body` に置く:

```php
use BEAR\Streamer\StreamTransferInject;

class MediaStream extends ResourceObject
{
    use StreamTransferInject;

    public function onGet(int $id): static
    {
        $media = $this->media->item($id);
        if ($media === null) {
            return $this->notFound($id, 'Media not found');
        }

        $filename = basename($media->filename);
        $path = $this->appMeta->appDir . '/var/media/' . $filename;
        $stream = $this->openReadStream($path);
        if ($stream === null) {
            return $this->notFound($id, 'Media file not found', $filename);
        }

        $size = $this->streamSize($stream);
        if ($size === null) {
            fclose($stream);

            return $this->notFound($id, 'Media file not found', $filename);
        }

        $this->headers['Content-Type'] = $media->mimeType;
        $this->headers['Content-Length'] = (string) $size;
        $this->headers['Content-Disposition'] = sprintf(
            'attachment; filename="%s"',
            strtr($filename, ['\\' => '\\\\', '"' => '\\"']),
        );
        $this->body = $stream;

        return $this;
    }
}
```

### 404分岐

streamを開かず（開いた後なら閉じて）JSON error bodyへ戻す。`Content-Length` / `Content-Disposition` はセットしない:

```php
private function notFound(int $id, string $message, string|null $filename = null): static
{
    $this->code = Code::NOT_FOUND;
    $this->headers['Content-Type'] = 'application/json';
    $this->body = ['message' => $message, 'id' => $id];
    if ($filename !== null) {
        $this->body['filename'] = $filename;
    }

    return $this;
}
```

### Test

3つのheaderと、`transfer()` 経由の出力がファイルのバイト列と一致することを検証する:

```php
$ro = $this->resource->get('app://self/variations/mediastream', ['id' => 5]);

$this->assertSame(200, $ro->code);
$this->assertSame('image/svg+xml', $ro->headers['Content-Type']);
$this->assertSame((string) strlen($expectedBytes), $ro->headers['Content-Length']);
$this->assertSame(
    'attachment; filename="media-005.svg"',
    $ro->headers['Content-Disposition'],
);
$this->assertTrue(is_resource($ro->body));

ob_start();
$ro->transfer(new class implements TransferInterface {
    /** @param array<string, mixed> $server */
    public function __invoke(ResourceObject $ro, array $server): void
    {
        unset($ro, $server);
    }
}, []);
$streamed = ob_get_clean();

$this->assertSame($expectedBytes, $streamed);
```

## Naming

stream転送は comparison-only の Variation として `src/Resource/App/Variations/` に置く — 正規形の `src/Resource/App/Media.php` はJSON形のまま:

| 種別 | 置き場所 / 形 | 例 |
|---|---|---|
| 正規 Resource（JSON形） | `src/Resource/App/<Entity>.php` | `Media.php` |
| stream Variation | `src/Resource/App/Variations/<Entity>Stream.php` | `MediaStream.php` |
| メタデータ read | `item(int $id): <Entity>\|null` | `#[DbQuery('media_item')]` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] 通常のMedia応答はJSON形（`src/Resource/App/Media.php`）が正規で、streamにするのは表現がファイル本体そのものである時のみと確認したか。
- [ ] `StreamTransferInject` を使い、open stream resource を `$this->body` に置くと決めたか。
- [ ] `Content-Type` / `Content-Length` / `Content-Disposition` を明示すると決めたか。
- [ ] stream success body には `#[JsonSchema]` を付けないと理解したか。

## Source

- [`src/Resource/App/Variations/MediaStream.php::onGet()`](../src/Resource/App/Variations/MediaStream.php)
- [`src/Resource/App/Media.php`](../src/Resource/App/Media.php)
- [`src/Query/MediaQueryInterface.php::item()`](../src/Query/MediaQueryInterface.php)
- [`src/Entity/Media.php`](../src/Entity/Media.php)
- [`var/media/media-005.svg`](../var/media/media-005.svg)

## Tests

- [`tests/Resource/App/Variations/MediaStreamTest.php`](../tests/Resource/App/Variations/MediaStreamTest.php)

## Key points

`StreamTransferInject` を使い、`Content-Type`, `Content-Length`, `Content-Disposition` を明示し、open stream resourceを `$this->body` に置く。body全体をstreamにする必要はなく、bodyの一部にstreamを混ぜると既存rendererと共存できる（manual「With Renderers」）。404分岐ではstreamを開かずJSON error bodyへ戻し、Length/Dispositionヘッダはセットしない。

## Do not

- success stream body に `#[JsonSchema]` を付けない — bodyはJSON文書ではなくstream resourceなので検証対象にならない。正規のJSON形 `Media::onGet()` から属性ごとコピーしがち。

## マスター確認（After）

- [ ] Resource が `StreamTransferInject` を使い、body が stream resource。
- [ ] 3つのheader（Type/Length/Disposition）がセットされ、404では不在。
- [ ] `transfer()` 経由の出力がファイルのバイト列と一致することを `MediaStreamTest.php` 相当で green。

## See also

- [`db-read-one-entity`](./db-read-one-entity.md) — stream前のメタデータ取得（`item(int $id)` read）の型
- [`not-found-response`](./not-found-response.md) — 404のidiom（stream版でも同じ）
- [`api-get-hal-resource`](./api-get-hal-resource.md) — 正規のJSON形応答
- [`file-upload-input`](./file-upload-input.md) — ファイルを受ける側（書き込み方向）
- [`content-negotiation`](./content-negotiation.md) — 表現を出し分けるもう1つの型（Acceptヘッダ）
- [`json-schema-validation`](./json-schema-validation.md) — JSON形bodyの検証（stream bodyには適用しない）
