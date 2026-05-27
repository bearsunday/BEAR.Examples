<?php

declare(strict_types=1);

namespace MyVendor\Cms\Attribute;

use Attribute;

/** @codeCoverageIgnore */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final readonly class Validate
{
    /** @param class-string $service */
    public function __construct(
        public string $service,
        public string $method = '__invoke',
    ) {
    }
}
