<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\Kata\Auth\AdminGuard;
use BEAR\Kata\Entity\Article;
use BEAR\Kata\Factory\ArticleFactory;
use BEAR\Kata\Query\ArticleQueryInterface;
use Ray\AuraSqlModule\Pagerfanta\Page as PagerPage;

use function assert;
use function ceil;
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
        private readonly AdminGuard $admin,
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
    ) {
    }

    public function onGet(
        string|null $status = null,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
        int $deleted = 0,
    ): static {
        $admin = $this->admin->user();
        $page = max(1, $page);
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
        $pages = $this->article->list(
            authorId: $admin->authorId(),
            status: $status,
            perPage: $perPage,
        );
        $totalPages = max(1, (int) ceil(count($pages) / $perPage));
        $page = min($page, $totalPages);
        $articlePage = $pages[$page];
        assert($articlePage instanceof PagerPage);
        /** @var list<array<string, mixed>> $rows */
        $rows = $articlePage->data;
        $articles = $this->articleFactory->fromRows($rows);

        $this->body = [
            'articles' => $articles,
            'filter' => ['status' => $status],
            'page' => $articlePage->current,
            'perPage' => $articlePage->maxPerPage,
            'hasNext' => $articlePage->hasNext,
            'deleted' => $deleted === 1,
        ];

        return $this;
    }
}
