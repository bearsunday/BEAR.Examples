<?php

declare(strict_types=1);

namespace MyVendor\Cms\Entity;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
