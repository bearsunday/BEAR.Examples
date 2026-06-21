<?php

declare(strict_types=1);

namespace BEAR\Kata\Query;

use Ray\MediaQuery\Annotation\DbQuery;

interface ArticleTagCommandInterface
{
    /** Drop every tag link for an article (used before a fresh replace). */
    #[DbQuery('article_tag_clear')]
    public function clear(int $articleId): void;

    /** Link one article ↔ tag pair. Resource layer loops over a tagIds list. */
    #[DbQuery('article_tag_link')]
    public function link(int $articleId, int $tagId): void;
}
