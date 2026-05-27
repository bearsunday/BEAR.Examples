<?php

declare(strict_types=1);

namespace Ray\Csrf\Http;

/**
 * Wire-protocol field name for the CSRF token (default `_csrf_token`).
 *
 * Single source of truth for both `ServerRequestBodyToken` (which reads
 * `$_POST[$name]`) and the consumer's HTML templates (which render
 * `<input name="…">`). The consumer typically exposes this name to its
 * view layer alongside the issued token.
 */
final readonly class CsrfTokenField
{
    public function __construct(public string $name = '_csrf_token')
    {
    }
}
