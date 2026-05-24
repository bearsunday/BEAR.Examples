<?php

declare(strict_types=1);

namespace MyVendor\Cms\Attribute;

use Attribute;

/**
 * Method-level marker for `SameOriginInterceptor`.
 *
 * Apply to `onPost` (and other unsafe verbs) on Page/Admin resources to
 * enforce a same-origin policy on browser-driven write requests:
 *
 * ```php
 * #[SameOrigin]
 * public function onPost(int $id): static
 * {
 *     // …
 * }
 * ```
 *
 * The attribute is intentionally bare — the matched origin and detection
 * algorithm live in `SameOriginInterceptor`, not in attribute parameters.
 * Per-route customisation (e.g. per-action allowlists) hasn't surfaced as
 * a real need; if it does, it lives on a paired attribute, not as fields
 * smuggled into this marker.
 *
 * Targets methods only — class-level application is not supported. Want
 * blanket coverage of a namespace? Express it in the module pointcut
 * instead of widening this attribute (Codex review note: attribute-class
 * targets blur which methods are gated).
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SameOrigin
{
}
