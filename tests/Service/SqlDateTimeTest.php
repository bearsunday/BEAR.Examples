<?php

declare(strict_types=1);

namespace BEAR\Examples\Service;

use PHPUnit\Framework\TestCase;

final class SqlDateTimeTest extends TestCase
{
    private SqlDateTime $sqlDateTime;

    protected function setUp(): void
    {
        $this->sqlDateTime = new SqlDateTime();
    }

    public function testFromRfc3339ReturnsNullForNullOrEmpty(): void
    {
        $this->assertNull($this->sqlDateTime->fromRfc3339(null));
        $this->assertNull($this->sqlDateTime->fromRfc3339(''));
    }

    public function testFromRfc3339ConvertsZuluTimestampToSqlDateTime(): void
    {
        $this->assertSame('2026-06-01 04:43:50', $this->sqlDateTime->fromRfc3339('2026-06-01T04:43:50Z'));
    }

    public function testFromRfc3339NormalisesOffsetTimestampToUtcSqlDateTime(): void
    {
        $this->assertSame('2026-06-01 04:43:50', $this->sqlDateTime->fromRfc3339('2026-06-01T13:43:50+09:00'));
    }

    public function testToRfc3339UtcNormalisesOffsetTimestampToUtc(): void
    {
        $this->assertSame('2026-06-01T04:43:50Z', $this->sqlDateTime->toRfc3339Utc('2026-06-01T13:43:50+09:00'));
    }
}
