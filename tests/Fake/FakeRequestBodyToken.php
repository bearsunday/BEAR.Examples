<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake;

use Override;
use Ray\Csrf\Http\RequestBodyTokenInterface;

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
