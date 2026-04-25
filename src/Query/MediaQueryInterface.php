<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Media;
use Ray\MediaQuery\Annotation\DbQuery;

interface MediaQueryInterface
{
    #[DbQuery('media_by_id', type: 'row')]
    public function getById(int $id): Media|null;

    #[DbQuery('media_by_filename', type: 'row')]
    public function getByFilename(string $filename): Media|null;
}
