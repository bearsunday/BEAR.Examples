<?php

declare(strict_types=1);

namespace MyVendor\Cms\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use Throwable;

final class OAuthConfigurationException extends BadRequestException
{
    public function __construct(
        string $message = 'Google OAuth is not configured.',
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, Code::SERVICE_UNAVAILABLE, $previous);
    }
}
