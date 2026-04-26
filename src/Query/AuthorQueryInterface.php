<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Author;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorQueryInterface
{
    #[DbQuery('author_item', type: 'row')]
    public function item(int $id): Author|null;

    #[DbQuery('author_by_email', type: 'row')]
    public function byEmail(string $email): Author|null;
}
