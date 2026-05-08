<?php

declare(strict_types=1);

namespace MyVendor\Cms\Query\Samples;

use Ray\MediaQuery\Annotation\DbQuery;
use Ray\MediaQuery\Result\AffectedRows;

interface ArticleAffectedRowsCommandInterface
{
    #[DbQuery('article_update')]
    public function update(
        int $id,
        string $title,
        string $body,
        string|null $excerpt,
        string $status,
        string|null $publishedAt,
    ): AffectedRows;

    #[DbQuery('article_delete')]
    public function delete(int $id): AffectedRows;
}
