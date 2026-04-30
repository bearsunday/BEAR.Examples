<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Entity\Tag;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;

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
        $offset = ($page - 1) * $perPage;

        $articles = $this->article->list(
            categoryId: $categoryId,
            tagId: $tagId,
            authorId: $authorId,
            status: $status,
            limit: $perPage,
            offset: $offset,
        );

        $this->body = [
            'articles' => $articles,
            'filter' => [
                'categoryId' => $categoryId,
                'tagId' => $tagId,
                'authorId' => $authorId,
                'status' => $status,
            ],
            'category' => $categoryId === null ? null : $this->category->item($categoryId),
            'tag' => $tagId === null ? null : $this->tag->item($tagId),
            'author' => $authorId === null ? null : $this->author->item($authorId),
            'page' => $page,
            'perPage' => $perPage,
            'hasNext' => count($articles) === $perPage,
        ];

        return $this;
    }
}
