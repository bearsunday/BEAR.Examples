<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Factory\ArticleFactory;
use MyVendor\Cms\Query\ArticleQueryInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function assert;
use function max;

/** @property array{articles: list<Article>} $body */
class Index extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
    ) {
    }

    public function onGet(int $limit = 10): static
    {
        $pages = $this->article->list(status: 'published', perPage: max(1, $limit));
        $articlePage = $pages[1];
        assert($articlePage instanceof Page);
        /** @var list<array<string, mixed>> $rows */
        $rows = $articlePage->data;
        $articles = $this->articleFactory->fromRows($rows);

        $this->body = ['articles' => $articles];

        return $this;
    }
}
