<?php

declare(strict_types=1);

namespace MyVendor\Cms\Smoke;

use PDOStatement;
use Ray\Di\InjectorInterface;
use Ray\MediaQuery\FetchInterface;

final class FakeEntityFetch implements FetchInterface
{
    /** @return list<mixed> */
    public function fetchAll(PDOStatement $pdoStatement, InjectorInterface $injector): array
    {
        return [];
    }
}
