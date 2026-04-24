<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Entity\Media;
use Ray\MediaQuery\Annotation\DbQuery;

interface MediaQueryInterface
{
    #[DbQuery('get_media', type: 'row')]
    public function get(int $id): Media|null;

    #[DbQuery('get_media_by_filename', type: 'row')]
    public function getByFilename(string $filename): Media|null;
}
