<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

/**
 * Wire-protocol field name for the CSRF token (default `_csrf_token`).
 *
 * Single source of truth for both `ServerRequestBodyToken` (which reads
 * `$_POST[$name]`) and the Qiq templates (which render
 * `<input name="…">`). Defined once in `CsrfModule`, exposed to templates
 * via `CmsQiqRenderer::commonVars()`.
 */
final readonly class CsrfTokenField
{
    public function __construct(public string $name = '_csrf_token')
    {
    }
}
