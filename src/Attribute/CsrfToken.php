<?php

declare(strict_types=1);

namespace MyVendor\Cms\Attribute;

use Attribute;

/**
 * Method-level marker for `CsrfTokenInterceptor`.
 *
 * Stacks on top of `#[SameOrigin]` for destructive / session-changing
 * Page/Admin POSTs — the kind of operation where a same-origin
 * compromise (XSS in a sibling subdomain, sloppy SameSite defaults
 * on a related origin) would do the most damage. The Page resource
 * displaying the form embeds `_csrf_token` from
 * `CsrfTokenInterface::issue()`; the interceptor verifies the
 * submitted value before the resource method runs.
 *
 * Naming follows the @NaokiTsuchiya note in Issue #37 — the
 * attribute describes what the resource requires, not the attack
 * being defended against. `#[CsrfToken]` reads as a precondition;
 * `#[Csrf]` would read as a defence implementation detail.
 *
 * Targets methods only — class-level application would obscure which
 * POSTs are gated. Blanket coverage of a namespace, should that ever
 * be wanted, belongs in the module pointcut rather than attribute
 * widening.
 */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class CsrfToken
{
}
