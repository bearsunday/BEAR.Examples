<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query;

use MyVendor\Cms\Factory\ArticleFactory;
use MyVendor\Cms\Result\ArticleSelection;
use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleSelectionQueryInterface
{
    #[DbQuery('article_selection_list', factory: ArticleFactory::class)]
    public function list(string|null $status = null): ArticleSelection;
}
