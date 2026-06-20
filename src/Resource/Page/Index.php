<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Entity\Article;
use BEAR\Examples\Factory\ArticleFactory;
use BEAR\Examples\Query\ArticleQueryInterface;
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
