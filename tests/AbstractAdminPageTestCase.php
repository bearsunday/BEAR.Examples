<?php

declare(strict_types=1);

namespace MyVendor\Cms;

use MyVendor\Cms\Auth\AdminUser;

abstract class AbstractAdminPageTestCase extends AbstractPageTestCase
{
    protected function setUp(): void
    {
        $this->resource = $this->resourceWithUser(new AdminUser(
            id: 'test-admin-1',
            email: 'evelyn.moore1@example.com',
            name: 'Evelyn Moore',
            authorId: 1,
        ));
    }
}
