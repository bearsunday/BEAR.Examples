<?php

declare(strict_types=1);

namespace Ray\Csrf\Http;

/**
 * Wire-protocol field name for the CSRF token (default `_csrf_token`).
 *
 * Single source of truth for both ServerRequestBodyToken (which reads the
 * submitted POST field by this name) and the consumer's HTML templates
 * (which render the matching hidden input). The consumer typically exposes
 * this name to its view layer alongside the issued token.
 */
final readonly class CsrfTokenField
{
    public function __construct(public string $name = '_csrf_token')
    {
    }
}
