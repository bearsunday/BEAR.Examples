<?php

declare(strict_types=1);

namespace Ray\Csrf\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use Throwable;

/**
 * Thrown by `SameOriginInterceptor` / `CsrfTokenInterceptor` when a
 * request fails the policy. Carries the framework's `FORBIDDEN`
 * (403) code so the default 4xx pipeline produces the right HTTP
 * response without a per-app exception mapping layer.
 */
final class ForbiddenException extends BadRequestException
{
    public function __construct(string $message = 'Forbidden', Throwable|null $previous = null)
    {
        parent::__construct($message, Code::FORBIDDEN, $previous);
    }
}
