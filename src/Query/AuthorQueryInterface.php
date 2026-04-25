<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Author;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorQueryInterface
{
    #[DbQuery('author_by_id', type: 'row')]
    public function getById(int $id): Author|null;

    #[DbQuery('author_by_email', type: 'row')]
    public function getByEmail(string $email): Author|null;
}
