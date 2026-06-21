<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Kata\Entity\Article;
use BEAR\Kata\Entity\ArticleStatus;
use BEAR\Kata\Entity\Author;
use BEAR\Kata\Entity\Category;
use BEAR\Kata\Entity\Tag;
use BEAR\Kata\Factory\ArticleFactory;
use BEAR\Kata\Query\ArticleQueryInterface;
use BEAR\Kata\Query\AuthorQueryInterface;
use BEAR\Kata\Query\CategoryQueryInterface;
use BEAR\Kata\Query\TagQueryInterface;
use Ray\AuraSqlModule\Pagerfanta\Page as PagerPage;

use function assert;
use function ceil;
use function count;
use function max;
use function min;

/**
 * @property array{
 *     articles: list<Article>,
 *     filter: array{categoryId: int|null, tagId: int|null, authorId: int|null, status: string|null},
 *     category: Category|null,
 *     tag: Tag|null,
 *     author: Author|null,
 *     page: int,
 *     perPage: int,
 *     hasNext: bool,
 * } $body
 */
class ArticleList extends ResourceObject
{
    private const int DEFAULT_PER_PAGE = 10;
    private const int MAX_PER_PAGE = 100;

    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
        private readonly CategoryQueryInterface $category,
        private readonly TagQueryInterface $tag,
        private readonly AuthorQueryInterface $author,
    ) {
    }

    public function onGet(
        int|null $categoryId = null,
        int|null $tagId = null,
        int|null $authorId = null,
        string|null $status = null,
        int $page = 1,
        int $perPage = self::DEFAULT_PER_PAGE,
    ): static {
        $page = max(1, $page);
        $perPage = max(1, min(self::MAX_PER_PAGE, $perPage));
        $publicStatus = ArticleStatus::Published->value;
        $pages = $this->article->list(
            categoryId: $categoryId,
            tagId: $tagId,
            authorId: $authorId,
            status: $publicStatus,
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
            'filter' => [
                'categoryId' => $categoryId,
                'tagId' => $tagId,
                'authorId' => $authorId,
                'status' => $status === $publicStatus ? $publicStatus : null,
            ],
            'category' => $categoryId === null ? null : $this->category->item($categoryId),
            'tag' => $tagId === null ? null : $this->tag->item($tagId),
            'author' => $authorId === null ? null : $this->author->item($authorId),
            'page' => $articlePage->current,
            'perPage' => $articlePage->maxPerPage,
            'hasNext' => $articlePage->hasNext,
        ];

        return $this;
    }
}
