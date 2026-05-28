<?php

declare(strict_types=1);

namespace MyVendor\Cms\Attribute;

use Attribute;

/** Marks a method as requiring a per-session CSRF token; gate is `CsrfTokenInterceptor`. */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class CsrfToken
{
}
