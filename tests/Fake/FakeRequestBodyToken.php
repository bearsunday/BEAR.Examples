<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Http\RequestBodyTokenInterface;
use Override;

final readonly class FakeRequestBodyToken implements RequestBodyTokenInterface
{
    public function __construct(private string|null $submitted = FakeCsrfToken::DEFAULT_TOKEN)
    {
    }

    #[Override]
    public function submitted(): string|null
    {
        return $this->submitted;
    }
}
