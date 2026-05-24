<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

/**
 * Resolves the canonical origin that browser-driven write requests must
 * match (e.g. `https://cms.example.com`).
 *
 * Returning `null` is the "no origin gate" mode — used by tests, the CLI
 * runtime, and local dev where the value is unset. `SameOriginInterceptor`
 * treats `null` as a signal to short-circuit (no header inspection at all)
 * so unit tests and CLI smokes don't need to script `Sec-Fetch-Site` /
 * `Origin` / `Referer`.
 *
 * **Production gotcha.** Because `null` means "allow", a production
 * deployment that forgets to set `CMS_ALLOWED_ORIGIN` silently disables
 * the gate. The fail-closed path belongs in a `ProdModule` (boot-time
 * abort when the value is `null` under HTTP) — that module isn't in
 * scope for this PR; see `docs/scope.md` Tier 2 "Production tuning notes".
 */
interface AllowedOriginInterface
{
    /**
     * Canonical origin string (e.g. `https://cms.example.com`), or `null`
     * when no gate is configured (test / CLI / dev).
     */
    public function value(): string|null;
}
