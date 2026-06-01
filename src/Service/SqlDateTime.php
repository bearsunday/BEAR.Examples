<?php

declare(strict_types=1);

namespace MyVendor\Cms\Service;

use DateTimeImmutable;
use DateTimeZone;

final class SqlDateTime
{
    private const string FORMAT = 'Y-m-d H:i:s';

    public function fromRfc3339(string|null $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (new DateTimeImmutable($value))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(self::FORMAT);
    }
}
