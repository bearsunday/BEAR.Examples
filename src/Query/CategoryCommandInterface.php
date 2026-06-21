<?php

declare(strict_types=1);

namespace BEAR\Kata\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface CategoryCommandInterface
{
    #[DbQuery('category_add')]
    public function add(
        string $slug,
        string $name,
        string|null $description,
        int|null $parentId,
    ): void;

    #[DbQuery('category_update')]
    public function update(
        int $id,
        string $name,
        string|null $description,
        int|null $parentId,
    ): void;

    #[DbQuery('category_delete')]
    public function delete(int $id): void;
}
