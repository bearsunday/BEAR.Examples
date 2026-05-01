<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Query\ArticleQueryInterface;

/** @property array{articles: list<Article>} $body */
class Index extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
    ) {
    }

    public function onGet(int $limit = 10): static
    {
        $this->body = [
            'articles' => $this->article->list(status: 'published', limit: $limit),
        ];

        return $this;
    }
}
