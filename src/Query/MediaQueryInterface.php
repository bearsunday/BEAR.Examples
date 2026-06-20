<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use BEAR\Examples\Entity\Media;
use Ray\MediaQuery\Annotation\DbQuery;

interface MediaQueryInterface
{
    #[DbQuery('media_item')]
    public function item(int $id): Media|null;

    #[DbQuery('media_by_filename')]
    public function byFilename(string $filename): Media|null;
}
