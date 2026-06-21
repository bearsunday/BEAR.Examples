<?php

declare(strict_types=1);

namespace BEAR\Kata\Exception;

use RuntimeException;

use function implode;

final class MissingAuth0ConfigurationException extends RuntimeException
{
    /** @param list<string> $names */
    public function __construct(array $names)
    {
        parent::__construct('Missing Auth0 configuration: ' . implode(', ', $names));
    }
}
