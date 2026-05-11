# Upstream issue draft: bear/async 0.2 silently skips parallel execution under HAL renderer

Draft for filing against `bearsunday/BEAR.Async` (primary), with a small
coordinated change in `bearsunday/BEAR.Resource` for the HAL renderer
evaluation path.

---

## Title

`AsyncRequest` is silently dropped by `HalRenderer`, breaking `#[Embed]` parallel execution for HAL responses

## Summary

After upgrading to `bear/async ^0.2`, HAL+JSON responses lose
`_embedded` entirely and report a misleading ~2x speedup because the
parallel batch never fires. The async embed interceptor wraps each
`AbstractRequest` into an `AsyncRequest` (a `final` class that
implements only `Stringable`), but `HalRenderer::valuateElements()`
walks only `AbstractRequest` instances — so the wrapped objects are
skipped before their `__toString()` (which is what flushes
`PendingRequests`) is ever invoked. The result is a JSON body that
contains bare `{uri, query}` data objects in place of `_embedded`, and
no parallel execution.

Qiq / template-engine responses are unaffected because the template
itself string-casts each value, which matches the documented design
(`vendor/bear/async/src/PendingRequests.php:19`: "Template engine calls
__toString() on AsyncRequest").

## Reproduction

`MyVendor.Cms` showcase project (BEAR.Sunday reference CMS, see PR
[bearsunday/MyVendor.Cms#31](https://github.com/bearsunday/MyVendor.Cms/pull/31)):

```bash
git clone https://github.com/bearsunday/MyVendor.Cms
cd MyVendor.Cms
composer install
composer docker:async-build
composer docker:async-test     # ArticleAsyncTest assertions
composer docker:async-demo     # timing demo
```

Demo output (Docker, PHP 8.5 ZTS + ext-parallel):

```text
BEAR.Async Article embed demo
-----------------------------
Resource code: unchanged Article::onGet with three #[Embed] dependencies.
Timing context: fake data + demo-only 150ms delay on Author/Category/Tags.

Timing
------
sync:          944.12 ms
async warmup:  468.55 ms
async:         467.41 ms
speedup:       2.02x

Verification
------------
same HAL representation: yes
embedded resources: 0       ← should be 3
```

`embedded resources: 0` is the giveaway. The 2x "speedup" is three
150ms delays that simply did not run on the async path, not three that
ran in parallel.

## Root cause

1. `vendor/bear/async/src/AsyncEmbedInterceptor.php:59-60` — wraps each
   `AbstractRequest` in the body:
   ```php
   if ($value instanceof AbstractRequest) {
       return new AsyncRequest($value, $this->allRequests);
   }
   ```

2. `vendor/bear/async/src/AsyncRequest.php:20` — `AsyncRequest` is a
   `final` class that does **not** extend `AbstractRequest`:
   ```php
   final class AsyncRequest implements Stringable
   {
       public readonly string $uri;
       public readonly array $query;
       public function __construct(
           private readonly AbstractRequest $inner,
           private readonly PendingRequests $pendingRequests,
       ) { ... }
       public function __toString(): string {
           return $this->pendingRequests->getResult($this->uri);  // batch flush
       }
   }
   ```

3. `vendor/bear/resource/src/HalRenderer.php:78` — only walks
   `AbstractRequest` instances:
   ```php
   foreach ($ro->body as $key => &$embeded) {
       if (! ($embeded instanceof AbstractRequest)) {
           continue;   // ← AsyncRequest skipped here
       }
       // ... evaluate and move to _embedded ...
   }
   ```

Net effect: `AsyncRequest` objects sit in `$ro->body` unchanged,
`__toString()` is never invoked, `PendingRequests` never flushes, and
`Hal::asJson()` serialises only the public `uri`/`query` properties.

## Proposed fix

Two coordinated changes. The architectural principle is:
**parallelisation is the interceptor's concern, not the renderer's** —
the body shape downstream should be indistinguishable from synchronous
execution.

### 1. `bear/async`: `AsyncRequest extends AbstractRequest`

```php
final class AsyncRequest extends AbstractRequest
{
    public function __construct(
        private readonly AbstractRequest $inner,
        private readonly PendingRequests $pendingRequests,
    ) {
        parent::__construct(
            $inner->invoker,           // protected — accessible from subclass
            $inner->resourceObject,
            $inner->method,
            $inner->query,
            $inner->links,
            null,                      // linker not needed for embed evaluation
        );
        $pendingRequests->add($this);
    }

    /** @return ResourceObject */
    public function __invoke(array|null $query = null): ResourceObject
    {
        return ($this->inner)($query);
    }

    public function __toString(): string
    {
        return $this->pendingRequests->getResult($this->toUri());
    }
}
```

Key observation that makes this small: `AbstractRequest::__construct`
declares `protected InvokerInterface $invoker`, which is accessible to
subclasses on other `AbstractRequest` instances (PHP visibility rule).
`$linker` is `private` but nullable with default `null` — `AsyncRequest`
doesn't need linking. **No constructor change to `AbstractRequest` is
required.**

This makes every existing `instanceof AbstractRequest` site
(`HalRenderer.php:78`, `ResourceDonut.php:69-71`, any third-party
renderer or interceptor) correct for `AsyncRequest` automatically.

### 2. `bear/resource`: `HalRenderer` uses `(string)` cast to evaluate embeds

Currently `HalRenderer::valuateElements()` invokes embed evaluation as:

```php
$view = $this->render($embeded());   // line 98
```

Change to:

```php
$view = (string) $embeded;           // semantically equivalent for
                                     // AbstractRequest, enables parallel
                                     // batch fetch for AsyncRequest
```

This is **semantically equivalent** for `AbstractRequest`:
`AbstractRequest::__toString` (line 95) is
`$this->invoke(); return (string) $this->result;`, which produces the
same view string as `$this->render($embeded())`. For `AsyncRequest`,
`(string)` triggers `PendingRequests::getResult()` — the first call
runs the entire registered batch in parallel and returns the cached
view; subsequent calls return from cache.

Net result: HAL responses get correct `_embedded` content **and**
parallel execution.

The cross-schema branch (`HalRenderer.php:89-94`) can stay as
`$embeded()->body` (serial fallback for `AsyncRequest`); upgrading
cross-schema to also benefit from the parallel batch is a separate
follow-up.

### Why this beats alternatives

| Approach | Renderer change | bear/resource change | Reaches every renderer | Notes |
|----------|-----------------|----------------------|------------------------|-------|
| **Extend `AbstractRequest`** (proposed) | One-line widening | One-line `(string)` substitution | Yes — `instanceof AbstractRequest` everywhere already covers it | Recommended |
| Marker interface (`EmbedRequestInterface`) | New interface + add to `implements` lists | HalRenderer + every other renderer must learn the new check | No — each renderer needs an update | Bigger blast radius |
| Disable `AsyncEmbedInterceptor` for HAL context | none | none | n/a | Kills the feature, not a fix |

The "extend `AbstractRequest`" path was initially set aside because it
appeared to require constructor relaxation; on re-reading,
`protected $invoker` is already subclass-accessible and `$linker` is
nullable, so no upstream constructor change is needed.

## Backward compatibility

- `AsyncRequest` becomes a subclass of `AbstractRequest`. Public surface
  gains all inherited methods (`__invoke`, `withQuery`, `addQuery`,
  `toUri`, `hash`, `linkSelf`/`linkNew`/`linkCrawl`, ArrayAccess,
  Iterator, Serializable, JsonSerializable). All current callers that
  used only `__toString` continue to work.
- The `$inner` decorator field is retained as the source of truth for
  `__invoke` delegation.
- `HalRenderer::valuateElements()` change is semantically equivalent for
  the pre-existing `AbstractRequest` path.
- `final` on `AsyncRequest` can be retained (the class stays a leaf).

## Affected versions

- `bear/async` `0.2.x`
- `bear/resource` (current `HalRenderer`)

## Repro repo

[bearsunday/MyVendor.Cms#31](https://github.com/bearsunday/MyVendor.Cms/pull/31) — BEAR.Sunday reference CMS showcase that revealed the regression while migrating from `bear/async ^0.1` to `^0.2`.

## References

- `vendor/bear/async/src/AsyncRequest.php:20`
- `vendor/bear/async/src/AsyncEmbedInterceptor.php:59`
- `vendor/bear/async/src/PendingRequests.php:19`
- `vendor/bear/resource/src/HalRenderer.php:78,89,98`
- `vendor/bear/resource/src/AbstractRequest.php:42,81,95`
- `vendor/bear/query-repository/src/ResourceDonut.php:69`
