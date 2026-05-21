<?php

declare(strict_types=1);

namespace MyVendor\Cms\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use Throwable;

final class UnauthenticatedException extends BadRequestException
{
    public function __construct(string $message = 'Authentication required', Throwable|null $previous = null)
    {
        parent::__construct($message, Code::UNAUTHORIZED, $previous);
    }
}
