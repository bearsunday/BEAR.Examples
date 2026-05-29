<?php

declare(strict_types=1);

namespace Ray\Csrf\Exception;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\BadRequestException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ForbiddenExceptionTest extends TestCase
{
    public function testDefaultMessageAndCode(): void
    {
        $e = new ForbiddenException();
        $this->assertSame('Forbidden', $e->getMessage());
        $this->assertSame(Code::FORBIDDEN, $e->getCode());
    }

    public function testCarriesCustomMessageAndPrevious(): void
    {
        $previous = new RuntimeException('upstream');
        $e = new ForbiddenException('Cross-site rejected', $previous);
        $this->assertSame('Cross-site rejected', $e->getMessage());
        $this->assertSame($previous, $e->getPrevious());
    }

    public function testInheritsFromBadRequestException(): void
    {
        // Pins the framework integration: anything catching BadRequestException
        // also catches ForbiddenException, so the 4xx pipeline picks it up.
        $this->assertInstanceOf(BadRequestException::class, new ForbiddenException());
    }
}
