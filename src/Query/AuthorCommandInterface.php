<?php

declare(strict_types=1);

namespace BEAR\Kata\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorCommandInterface
{
    #[DbQuery('author_add')]
    public function add(string $name, string $email, string $bio): void;

    #[DbQuery('author_update')]
    public function update(int $id, string $name, string $email, string $bio): void;
}
