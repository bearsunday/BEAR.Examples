<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface TagCommandInterface
{
    #[DbQuery('tag_add')]
    public function add(string $slug, string $name): void;

    #[DbQuery('tag_update')]
    public function update(int $id, string $slug, string $name): void;

    #[DbQuery('tag_delete')]
    public function delete(int $id): void;
}
