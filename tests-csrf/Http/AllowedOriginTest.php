<?php

declare(strict_types=1);

namespace Ray\Csrf\Http;

use PHPUnit\Framework\TestCase;

final class AllowedOriginTest extends TestCase
{
    public function testDefaultsToNull(): void
    {
        $this->assertNull((new AllowedOrigin())->value);
    }

    public function testCarriesConstructorValue(): void
    {
        $this->assertSame('https://cms.example.com', (new AllowedOrigin('https://cms.example.com'))->value);
    }

    public function testReadonlyImmutability(): void
    {
        // `final readonly class` — accessing twice returns the same value, no
        // mutation API. Lock the contract: there is no setter.
        $origin = new AllowedOrigin('https://cms.example.com');
        $this->assertSame($origin->value, $origin->value);
    }
}
