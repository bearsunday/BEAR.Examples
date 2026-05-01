<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Author;
use Ray\MediaQuery\Annotation\DbQuery;

interface AuthorQueryInterface
{
    #[DbQuery('author_item')]
    public function item(int $id): Author|null;

    #[DbQuery('author_by_email')]
    public function byEmail(string $email): Author|null;

    /** @return list<Author> */
    #[DbQuery('author_list')]
    public function list(): array;
}
