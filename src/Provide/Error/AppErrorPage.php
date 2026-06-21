<?php

declare(strict_types=1);

namespace BEAR\Kata\Provide\Error;

use BEAR\Resource\ResourceObject;

final class AppErrorPage extends ResourceObject
{
    /** @param array<string, mixed> $body */
    public function __construct(int $code, array $body)
    {
        $this->code = $code;
        $this->headers = ['Content-Type' => 'application/json; charset=utf-8'];
        $this->body = ['code' => $code] + $body;
    }
}
