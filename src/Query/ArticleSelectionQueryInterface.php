<?php

declare(strict_types=1);

namespace BEAR\Kata\Query;

use BEAR\Kata\Factory\ArticleFactory;
use BEAR\Kata\Result\ArticleSelection;
use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleSelectionQueryInterface
{
    #[DbQuery('article_selection_list', factory: ArticleFactory::class)]
    public function list(string|null $status = null): ArticleSelection;
}
