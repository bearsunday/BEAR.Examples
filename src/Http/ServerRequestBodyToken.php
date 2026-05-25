<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

use function is_string;

/** @SuppressWarnings("PHPMD.Superglobals") Body adapter boundary. */
final readonly class ServerRequestBodyToken implements RequestBodyTokenInterface
{
    public const string FIELD_NAME = '_csrf_token';

    #[Override]
    public function submitted(): string|null
    {
        $value = $_POST[self::FIELD_NAME] ?? null;
        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }
}
