<?php

declare(strict_types=1);

namespace BEAR\Kata\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use Throwable;

final class ForbiddenException extends BadRequestException
{
    public function __construct(string $message = 'Forbidden', Throwable|null $previous = null)
    {
        parent::__construct($message, Code::FORBIDDEN, $previous);
    }
}
