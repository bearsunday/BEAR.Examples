<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Query\ArticleQueryInterface;

use function array_slice;
use function count;
use function max;
use function min;

/**
 * @property array{
 *     articles: list<Article>,
 *     filter: array{status: string|null},
 *     page: int,
 *     perPage: int,
 *     hasNext: bool,
 *     deleted: bool,
 * } $body
 */
class ArticleList extends ResourceObject
{
    private const int DEFAULT_PER_PAGE = 20;
    private const int MAX_PER_PAGE = 100;

    public function __construct(
        private readonly ArticleQueryInterface $article,
    ) {
    }

    public function onGet(
        string|null $status = null,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        int $deleted = 0,
    ): static {
        $page = max(1, $page);
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
        $offset = ($page - 1) * $perPage;

        $articles = $this->article->list(
            status: $status,
            limit: $perPage + 1,
            offset: $offset,
        );

        $hasNext = count($articles) > $perPage;
        $articles = array_slice($articles, 0, $perPage);

        $this->body = [
            'articles' => $articles,
            'filter' => ['status' => $status],
            'page' => $page,
            'perPage' => $perPage,
            'hasNext' => $hasNext,
            'deleted' => $deleted === 1,
        ];

        return $this;
    }
}
