<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

use function is_string;

/** @SuppressWarnings("PHPMD.Superglobals") Body adapter boundary. */
final readonly class ServerRequestBodyToken implements RequestBodyTokenInterface
{
    public function __construct(private CsrfTokenField $field)
    {
    }

    #[Override]
    public function submitted(): string|null
    {
        $value = $_POST[$this->field->name] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
