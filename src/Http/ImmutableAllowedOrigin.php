<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

/** Constructor-fixed `AllowedOriginInterface`. AppModule resolves the value once. */
final readonly class ImmutableAllowedOrigin implements AllowedOriginInterface
{
    public function __construct(private string|null $value = null)
    {
    }

    #[Override]
    public function value(): string|null
    {
        return $this->value;
    }
}
