# `file-upload-input`

**`#[InputFile]`でファイルアップロードを受ける** · [← 索引に戻る](../index.md)

- **Category:** Resource / API
- **Status:** `canonical`
- **Aliases:** file upload, InputFile, FileUpload, media upload, MIME validation, binary upload, multipart/form-data, Koriym\FileUpload, ファイルアップロード, 画像アップロード
- **Manual:** https://bearsunday.github.io/manuals/1.0/en/resource_param.html
- **Use when:** HTTP multipartアップロードでファイルを受け取り、検証して保存したい。

## 例

### Resource — `#[InputFile]` 宣言

検証条件を attribute で宣言する。違反は throw されず `ErrorFileUpload` として引数に届き、Resourceが400へ変換する:

```php
private const int MAX_UPLOAD_BYTES = 5_242_880;
private const array ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];
private const array ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

public function onPost(
    #[InputFile(
        maxSize: self::MAX_UPLOAD_BYTES,
        allowedTypes: self::ALLOWED_MIME_TYPES,
        allowedExtensions: self::ALLOWED_EXTENSIONS,
    )]
    FileUpload|ErrorFileUpload $file,
    string|null $alt = null,
): static {
    if ($file instanceof ErrorFileUpload) {
        return $this->badRequest($file->message ?? 'File upload failed');
    }

    $invalidMessage = $this->invalidFileMessage($file);
    if ($invalidMessage !== null) {
        return $this->badRequest($invalidMessage);
    }

    $uploadDir = $this->uploadDir();
    if (! $this->ensureUploadDir($uploadDir)) {
        return $this->serverError('Upload directory is not writable');
    }

    $filename = $this->storedFilename($file);
    $path = $uploadDir . '/' . $filename;
    if (! $file->move($path)) {
        return $this->serverError('Uploaded file could not be stored');
    }

    return $this->registerMedia($file, $filename, $path, $alt);
}
```

### 保存名 — 衝突・path traversal・上書き防止

```php
private function storedFilename(FileUpload $file): string
{
    $basename = basename($file->name);
    $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename);
    $safe = $safe === null || $safe === '' ? 'upload.' . (string) $file->extension : $safe;

    return bin2hex(random_bytes(8)) . '-' . $safe;
}
```

### メタデータ登録とロールバック

登録に失敗したら保存済みファイルを削除して500を返す:

```php
try {
    $this->mediaCmd->add(
        $filename,
        $file->type,
        $this->baseUrl() . '/' . $filename,
        $alt,
        0,
        0,
    );
    $created = $this->media->byFilename($filename);
} catch (Throwable) {
    $this->removeStoredFile($path);

    return $this->serverError('Uploaded media metadata could not be stored');
}
```

成功時は 201 + Location:

```php
$this->code = Code::CREATED;
$this->headers['Location'] = '/media?id=' . $created->id;
$this->body = [
    'id' => $created->id,
    'filename' => $filename,
    'url' => $created->url,
];
```

### Test — HTTPを起こさず resource param に直接渡す

```php
$file = FileUpload::fromFile($this->pngFixture());

$ro = $this->resource->post('app://self/media-upload', [
    'file' => $file,
    'alt' => 'Uploaded through InputFile',
]);

$this->assertSame(201, $ro->code);
$this->assertStringStartsWith('/media?id=', $ro->headers['Location']);
```

エラー系は `ErrorFileUpload` を組み立てて渡す:

```php
$ro = $this->resource->post('app://self/media-upload', [
    'file' => new ErrorFileUpload([
        'name' => '',
        'type' => '',
        'size' => 0,
        'tmp_name' => '',
        'error' => UPLOAD_ERR_NO_FILE,
    ]),
]);

$this->assertSame(400, $ro->code);
```

## Naming

Resource property は read が `$media`、write が `$mediaCmd`。保存後のメタデータ回収は自然キー（filename）で行う:

| 形 | メソッド | SQL ファイル |
|---|---|---|
| 自然キーで1件 | `byFilename(string $filename)` | `media_by_filename.sql` |
| 書き込み | `add(...)` | `media_add.sql` |

> 命名規則の全容は [conventions.md](../docs/conventions.md#3-naming) を参照。

## 着手前チェック（Before）

- [ ] `#[InputFile(maxSize: ..., allowedTypes: [...], allowedExtensions: [...])]` で検証を宣言し、違反は `ErrorFileUpload` として引数に届く（Resourceが400へ変換する）形にしたか。
- [ ] Resource側の再検証（空ファイル/サイズ/MIME/拡張子）は防御の二重化として置くと決めたか。
- [ ] upload失敗時はロールバック（保存ファイル削除）し、500 または 400 を返すと決めたか。

## Source

- [`src/Resource/App/MediaUpload.php`](../src/Resource/App/MediaUpload.php)

## Tests

- [`tests/Resource/App/MediaUploadTest.php`](../tests/Resource/App/MediaUploadTest.php)

## Key points

`#[InputFile]` で `FileUpload|ErrorFileUpload` を受け、検証後 `move()` で保存→メタデータ登録。失敗時はロールバック。保存名は `bin2hex(random_bytes(8))` + sanitize済みbasenameで衝突・path traversal・上書きを防ぐ。テストはHTTPを起こさず `FileUpload::fromFile()` / `new ErrorFileUpload(...)` をresource paramに直接渡す。登録後の新規ID回収は `byFilename()`（→ [`db-read-by-natural-key`](./db-read-by-natural-key.md)）。

## Do not

- `image/svg+xml` を安易に `allowedTypes` に入れない — SVGはscriptを内包できる。本リポジトリはSVG uploadの400拒否をテストで固定している。

## マスター確認（After）

- [ ] 不正MIME / 超過サイズ / 空ファイル が 400 で拒否される。
- [ ] 正常アップロードで 201 + Location が返ることを `MediaUploadTest.php` 相当で green。

## See also

- [`api-post-input-dto`](./api-post-input-dto.md) — ファイル以外のPOST入力（DTO）の受け方
- [`db-read-by-natural-key`](./db-read-by-natural-key.md) — INSERT後のID回収（`byFilename`）
- [`json-schema-validation`](./json-schema-validation.md) — 入出力のJsonSchema検証
- [`web-context-param-binding`](./web-context-param-binding.md) — attributeによるparameter bindingの仲間
- [`app-resource-test`](./app-resource-test.md) — App resourceテストの土台
- [`form-validation-webform`](./form-validation-webform.md) — HTMLフォーム入力の検証
