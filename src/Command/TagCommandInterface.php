<?php

declare(strict_types=1);

namespace MyVendor\Cms\Command;

use Ray\MediaQuery\Annotation\DbQuery;

interface TagCommandInterface
{
    #[DbQuery('create_tag')]
    public function create(string $slug, string $name): void;

    #[DbQuery('delete_tag')]
    public function delete(int $id): void;
}
