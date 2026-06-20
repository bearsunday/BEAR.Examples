<?php

declare(strict_types=1);

namespace BEAR\Examples\Query;

use BEAR\Examples\Factory\ArticleFactory;
use BEAR\Examples\Result\ArticleSelection;
use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleSelectionQueryInterface
{
    #[DbQuery('article_selection_list', factory: ArticleFactory::class)]
    public function list(string|null $status = null): ArticleSelection;
}
