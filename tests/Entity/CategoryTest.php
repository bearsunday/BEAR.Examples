<?php

declare(strict_types=1);

namespace BEAR\Examples\Entity;

use PHPUnit\Framework\TestCase;

final class CategoryTest extends TestCase
{
    public function testIsTopLevel(): void
    {
        $top = new Category(1, 'tech', 'Technology', null, null);
        $this->assertTrue($top->isTopLevel());
        $this->assertFalse($top->hasParent());

        $child = new Category(2, 'php', 'PHP', null, 1);
        $this->assertFalse($child->isTopLevel());
        $this->assertTrue($child->hasParent());
    }
}
