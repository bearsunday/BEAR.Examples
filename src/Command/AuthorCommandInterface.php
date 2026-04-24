<?php

declare(strict_types=1);

namespace MyVendor\Cms\Command;

use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorCommandInterface
{
    #[DbQuery('create_author')]
    public function create(string $name, string $email, string $bio): void;

    #[DbQuery('update_author')]
    public function update(int $id, string $name, string $email, string $bio): void;
}
