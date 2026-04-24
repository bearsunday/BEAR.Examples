<?php

declare(strict_types=1);

namespace MyVendor\Cms\Command;

use Ray\MediaQuery\Annotation\DbQuery;

interface CategoryCommandInterface
{
    #[DbQuery('create_category')]
    public function create(
        string $slug,
        string $name,
        string|null $description,
        int|null $parentId,
    ): void;

    #[DbQuery('update_category')]
    public function update(
        int $id,
        string $name,
        string|null $description,
        int|null $parentId,
    ): void;

    #[DbQuery('delete_category')]
    public function delete(int $id): void;
}
