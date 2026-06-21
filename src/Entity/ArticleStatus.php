<?php

declare(strict_types=1);

namespace BEAR\Kata\Entity;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
}
