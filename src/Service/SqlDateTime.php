<?php

declare(strict_types=1);

namespace BEAR\Kata\Service;

use DateTimeImmutable;
use DateTimeZone;

final class SqlDateTime
{
    private const string RFC3339_UTC = 'Y-m-d\TH:i:s\Z';
    private const string SQL_FORMAT = 'Y-m-d H:i:s';

    public function fromRfc3339(string|null $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (new DateTimeImmutable($value))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(self::SQL_FORMAT);
    }

    public function toRfc3339Utc(string|null $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (new DateTimeImmutable($value))
            ->setTimezone(new DateTimeZone('UTC'))
            ->format(self::RFC3339_UTC);
    }
}
