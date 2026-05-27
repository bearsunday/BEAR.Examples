<?php

declare(strict_types=1);

namespace Ray\Csrf\Http;

use PHPUnit\Framework\TestCase;

final class CsrfTokenFieldTest extends TestCase
{
    public function testDefaultFieldNameIsCsrfToken(): void
    {
        $this->assertSame('_csrf_token', (new CsrfTokenField())->name);
    }

    public function testCarriesConstructorValue(): void
    {
        $this->assertSame('xsrf-token', (new CsrfTokenField('xsrf-token'))->name);
    }
}
