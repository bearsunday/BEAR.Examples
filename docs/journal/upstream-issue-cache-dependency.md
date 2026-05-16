# Upstream issue draft — BEAR.QueryRepository cross-resource dependency under HAL

> **RESOLVED** — fixed upstream in
> [bearsunday/BEAR.QueryRepository#174](https://github.com/bearsunday/BEAR.QueryRepository/pull/174),
> shipped in
> [release 1.16.0](https://github.com/bearsunday/BEAR.QueryRepository/releases/tag/1.16.0)
> (2026-05-16). `QueryRepository::setCacheDependency` now walks
> `$ro->body` for `AbstractRequest` children before HAL mutates the
> body, and the walk uses `AbstractRequest` rather than the concrete
> `Request` so `AsyncRequest` and other sibling implementations are
> covered too. The draft below is kept as a record of the diagnosis
> and the three candidate fixes considered; this issue was never
> posted upstream because the fix landed first.

Draft body that was prepared for filing against
`bearsunday/BEAR.QueryRepository`. Discovered while building the
`app://self/cache/*` showcase. The fix landed before the draft was
posted, so this is kept as a record of the diagnosis.

The local `xstep` wrapper was unusable during verification (missing
`vendor/autoload.php` under `~/.claude/plugins/marketplaces/xdebug-mcp/bin/`),
so the runtime evidence below was collected with `php -dxdebug.mode=trace`
directly against the PHP 8.5 binary. The trace artifact is at
`/tmp/xdebug-trace/trace.1597892585.xt.gz` on the author's machine; the
relevant lines are quoted in §"Runtime evidence".

---

## Title

`#[Cacheable]` parent never auto-merges Surrogate-Key from `#[Embed]`
children under HAL rendering (silent miss)

## Summary

When a `#[Cacheable]` parent embeds a `#[Cacheable]` child via `#[Embed]`,
the parent's stored `Surrogate-Key` is missing the child's URI tag, so a
PUT on the child does **not** invalidate the parent. The cause is order
of operations inside `QueryRepository::put`: `$ro->toString()` runs the
HAL renderer (which removes `Request` instances from `$ro->body` and
moves them under `_embedded` as already-decoded arrays) **before**
`EtagSetter::setCacheDependency` walks `$ro->body` looking for those
same `Request` instances. By the time `setCacheDependency` runs, no
`Request` remains in `$ro->body`, so `CacheDependency::depends()` is
never invoked.

The miss is silent: no error, no log line. Parent ETag is stored, but
without the child tag, so cross-resource invalidation doesn't fire.

## Repro

Minimal `#[Cacheable]` parent + child:

```php
#[Cacheable]
final class Author extends ResourceObject
{
    public function onGet(int $id): static
    {
        $this->body = ['id' => $id, 'name' => "Author $id"];
        return $this;
    }

    public function onPut(int $id, string $name): static
    {
        $this->body = ['id' => $id, 'name' => $name];
        return $this;
    }
}

#[Cacheable]
final class AuthorProfile extends ResourceObject
{
    #[Embed(rel: 'author', src: 'app://self/author')]
    public function onGet(int $authorId): static
    {
        $this->body['author']->addQuery(['id' => $authorId]);
        $this->body['authorId'] = $authorId;
        return $this;
    }
}
```

With `HalRenderer` configured (the default for `hal-api-app` context):

```text
GET  /authorprofile?authorId=1
 -> 200, ETag: E1, Surrogate-Key: (no tag for app://self/author?id=1)
GET  /authorprofile?authorId=1  -> 200, ETag: E1 (cache hit)
PUT  /author?id=1               -> 204 (invalidates app://self/author?id=1)
GET  /authorprofile?authorId=1  -> 200, ETag: E1 (STILL CACHED — bug)
```

Expected: the second GET after PUT returns a fresh ETag because the
parent should hold a Surrogate-Key tag pointing at `app://self/author?id=1`.

## Root cause

`vendor/bear/query-repository/src/QueryRepository.php` `put()`:

```php
public function put(ResourceObject $ro)
{
    $this->logger->log('put-query-repository', ['uri' => (string) $ro->uri]);
    $this->storage->deleteEtag($ro->uri);
    $ro->toString();                                  // ← line 38
    $cacheable = $this->getCacheableAnnotation($ro);
    $httpCache = $this->getHttpCacheAnnotation($ro);
    $ttl = $this->getExpiryTime($ro, $cacheable);
    ($this->headerSetter)($ro, $ttl, $httpCache);     // ← line 42
    // ...
}
```

`$ro->toString()` triggers `HalRenderer::valuateElements`
(`vendor/bear/resource/src/HalRenderer.php:93-115`):

```php
foreach ($ro->body as $key => &$embeded) {
    if (! ($embeded instanceof AbstractRequest)) {
        continue;
    }
    // ...
    unset($ro->body[$key]);                                            // ← removes Request
    $view = (string) $embeded;
    $ro->body['_embedded'][$key] = json_decode($view, null, 512, ...); // ← stores array
}
```

After `valuateElements`, `$ro->body` no longer contains any `Request`
instances. It contains an `_embedded` array of already-decoded child
bodies.

Then `($this->headerSetter)($ro, ...)` reaches
`EtagSetter::setCacheDependency`
(`vendor/bear/query-repository/src/EtagSetter.php:76-84`):

```php
private function setCacheDependency(ResourceObject $ro): void
{
    foreach ((array) $ro->body as $body) {
        if ($body instanceof Request && isset($body->resourceObject->headers[Header::ETAG])) {
            $this->cacheDeperency->depends($ro, $body->resourceObject);
        }
    }
}
```

The `$body instanceof Request` check finds zero matches because
HalRenderer already stripped them, so `depends()` is never called and
the parent's Surrogate-Key never gets the child's URI tag merged in.

## Runtime evidence

Trace excerpt (`/tmp/xdebug-trace/trace.1597892585.xt.gz`, gunzipped):

```text
277546  -> BEAR\QueryRepository\QueryRepository->put($ro)
        body keys: ['author' => Request, 'authorId' => 1, ...]
277601  -> BEAR\Resource\HalRenderer->renderHal($ro)            // from $ro->toString()
277604     unset($ro->body['author'])
277605     $ro->body['_embedded']['author'] = [...]              // decoded JSON, not a Request
277769  -> BEAR\QueryRepository\EtagSetter->__invoke($ro, ...)
277786     setCacheDependency: foreach finds 0 Request instances
            -> no depends() call
            -> $ro->headers[SURROGATE_KEY] not set
```

Final response headers contain `ETag: ...` but no `Surrogate-Key` for
the embedded child URI.

## Workaround (used in the showcase before 1.16.0)

Before the upstream fix landed, the showcase declared the child
dependency manually with one line of `fromAssoc`:

```php
#[Cacheable]
final class AuthorProfile extends ResourceObject
{
    public function __construct(private readonly UriTagInterface $uriTag) {}

    #[Embed(rel: 'author', src: 'app://self/author')]
    public function onGet(int $authorId): static
    {
        $this->body['author']->addQuery(['id' => $authorId]);
        $this->headers[Header::SURROGATE_KEY] = $this->uriTag->fromAssoc(
            'app://self/author{?id}',
            [['id' => $authorId]],
        );
        return $this;
    }
}
```

This worked because `CacheDependency::depends` ran `assert(! isset($from->headers[Header::SURROGATE_KEY]))`,
which held in this case because `setCacheDependency` found zero
Requests and never reached the assert — the manual `SURROGATE_KEY` was
written directly and survived.

The workaround was acceptable for single dependencies but made the
single-child case look like a manual-write API, which obscured the
intended user-zero-code design that `#[Embed]` + `#[Cacheable]` was
supposed to express. With 1.16.0 the showcase no longer needs this
line — `Cache\AuthorProfile` is now `#[Embed]`-only.

## Suggested fixes

### Option A — reorder `QueryRepository::put`

Move `headerSetter` before `toString()` so `EtagSetter` walks the
original body. Requires care: `EtagSetter::getEtagByEitireView` reads
`$ro->view`, which is populated by `toString()`. A two-pass approach is
needed:

1. Run `setCacheDependency` against the raw body (Requests still present).
2. Run `toString()` (HAL rendering).
3. Compute ETag against the now-populated `$ro->view`.

This is the more invariant-respecting fix but touches the put-pipeline
order.

### Option B — extend `EtagSetter::setCacheDependency` to also walk `_embedded`

After HalRenderer runs, the child bodies live under `$ro->body['_embedded'][$key]`
as already-decoded arrays. They no longer carry the `Request` /
`ResourceObject` reference, so the current `depends()` signature can't
use them.

A backward-compatible variant would require HalRenderer to preserve a
side-channel reference to the original `ResourceObject` (e.g. on the
parent's `_embedded` array under a non-public key, or as a property of
the renderer), which EtagSetter could then consult.

This is less invasive to ordering but adds a coupling between
HalRenderer and EtagSetter.

### Option C — make HalRenderer fire `depends()` itself

HalRenderer already has access to both parent and child `ResourceObject`
during `valuateElements` (line 89: `$maybeRequest->resourceObject->setRenderer($this)`).
It could call `$cacheDependency->depends($ro, $maybeRequest->resourceObject)`
directly before unsetting, which keeps the put-pipeline order intact.

This requires injecting `CacheDependencyInterface` into HalRenderer and
making the call a no-op when the parent isn't `#[Cacheable]`. Crosses a
package boundary (BEAR.Resource <- BEAR.QueryRepository), which is the
main argument against.

## Backward compatibility

- Option A: changes ordering inside one method, no public API change.
  Risk: third-party `HeaderSetter` implementations that assume `$ro->view`
  is populated before they run.
- Option B: HalRenderer change is internal, EtagSetter gains a new
  branch. No public API change.
- Option C: crosses package boundary. Requires HalRenderer constructor
  signature change or service-locator pattern.

## Affected pattern surface

Any HAL+JSON resource that uses `#[Cacheable]` (or `#[CacheableResponse]`)
on a parent with `#[Embed]` children. **The non-HAL JsonRenderer path is
not affected** because it doesn't unset `$ro->body` entries — `render()`
just calls `json_encode($ro)` which serialises Requests via their
`jsonSerialize()` without mutating the body array.

Empirical confirmation (body shape after `toString()` on the same
fabricated parent with a child Request in `body['author']`):

```text
=== HalRenderer ===
  body keys:        authorId, _embedded
  Request entries:  (none)
  → EtagSetter would call depends() 0 times   [BUG]

=== JsonRenderer ===
  body keys:        authorId, author
  Request entries:  author
  → EtagSetter would call depends() 1 time    [OK]
```

So the silent miss is HAL-renderer-specific. Given HAL is the
recommended representation for BEAR resources, the user-visible
consequence is significant: the documented `#[Cacheable]` + `#[Embed]`
composition fails to auto-track dependencies on exactly the projects
that follow the recommendation.

## Reference

`MyVendor.Cms` cache showcase lives at `src/Resource/App/Cache/*`.
Post-1.16 the canonical patterns are two shapes (see
`docs/conventions.md` § Cache):

- **Shape A** — `#[Embed]` alone for static single-child composition.
  `Cache\AuthorProfile` carries zero lines of cache code;
  `AuthorProfileCacheTest::testSourceHasNoManualCacheCode` pins
  `substr_count($src, 'fromAssoc(') === 0` and
  `substr_count($src, 'Header::SURROGATE_KEY') === 0`.
- **Shape B** — one `fromAssoc` line for body-derived
  variable-length dependency sets that `#[Embed]` cannot statically
  express. `Cache\ArticleTags` is the canonical example;
  `ArticleTagsCacheTest::testSourceHasExactlyOneFromAssocCall` pins
  the one-line invariant.
