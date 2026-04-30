<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Query\ArticleQueryInterface;

class Index extends ResourceObject
{
    /** @var array{articles: list<Article>} */
    public $body;

    public function __construct(
        private readonly ArticleQueryInterface $article,
    ) {
    }

    public function onGet(int $limit = 10): static
    {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = [
            'articles' => $this->article->list(status: 'published', limit: $limit),
        ];

        return $this;
    }
}
