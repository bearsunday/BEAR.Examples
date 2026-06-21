<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page\Admin;

use BEAR\Kata\AbstractPageTestCase;
use BEAR\Kata\Exception\UnauthenticatedException;

final class AuthBoundaryTest extends AbstractPageTestCase
{
    public function testVisitorCannotReachAdminIndex(): void
    {
        $this->expectException(UnauthenticatedException::class);
        $this->expectExceptionCode(401);

        $this->resource->get('page://self/admin/index');
    }

    public function testVisitorCannotReachAdminArticles(): void
    {
        $this->expectException(UnauthenticatedException::class);
        $this->expectExceptionCode(401);

        $this->resource->get('page://self/admin/articlelist');
    }
}
