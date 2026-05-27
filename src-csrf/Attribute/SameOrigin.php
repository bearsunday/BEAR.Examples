<?php

declare(strict_types=1);

namespace Ray\Csrf\Attribute;

use Attribute;

/** Marks a method as requiring same-origin headers; gate is `SameOriginInterceptor`. */
#[Attribute(Attribute::TARGET_METHOD)]
final readonly class SameOrigin
{
}
