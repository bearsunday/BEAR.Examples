<?php

declare(strict_types=1);

namespace MyVendor\Cms\Http;

use Override;

use function is_string;

/**
 * `$_POST`-backed `RequestBodyTokenInterface`.
 *
 * Boundary class for form-body access — `$_POST` is touched here and
 * only here. Mirrors `ServerRequestOrigin`'s shape so the two
 * request-state adapters are recognisably the same kind of object.
 *
 * @SuppressWarnings("PHPMD.Superglobals") Body adapter boundary; mirrors `ServerRequestOrigin`.
 */
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
