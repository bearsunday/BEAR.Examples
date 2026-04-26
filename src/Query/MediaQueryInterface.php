<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Media;
use Ray\MediaQuery\Annotation\DbQuery;

interface MediaQueryInterface
{
    #[DbQuery('media_item', type: 'row')]
    public function item(int $id): Media|null;

    #[DbQuery('media_by_filename', type: 'row')]
    public function byFilename(string $filename): Media|null;
}
