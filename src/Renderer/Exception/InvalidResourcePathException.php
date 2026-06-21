<?php

declare(strict_types=1);

namespace BEAR\Kata\Renderer\Exception;

use RuntimeException;

final class InvalidResourcePathException extends RuntimeException
{
    public function __construct(string $fileName)
    {
        parent::__construct('Resource file path must contain "src/Resource/": ' . $fileName);
    }
}
