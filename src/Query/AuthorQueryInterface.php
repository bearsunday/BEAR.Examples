<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Author;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorQueryInterface
{
    #[DbQuery('get_author', type: 'row')]
    public function get(int $id): Author|null;

    #[DbQuery('get_author_by_email', type: 'row')]
    public function getByEmail(string $email): Author|null;
}
