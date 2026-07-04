<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Kata\Entity\Article;
use BEAR\Kata\Entity\Category as CategoryEntity;
use BEAR\Kata\Factory\ArticleFactory;
use BEAR\Kata\Query\ArticleQueryInterface;
use BEAR\Kata\Query\CategoryQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function assert;

/** @property array{message: string}|array{category: CategoryEntity, articles: list<Article>} $body */
class Category extends ResourceObject
{
    private const int RECENT_LIMIT = 10;

    public function __construct(
        private readonly CategoryQueryInterface $category,
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
    ) {
    }

    public function onGet(int $id): static
    {
        $category = $this->category->item($id);
        if ($category === null) {
            $this->code = 404;
            $this->body = ['message' => 'Category not found'];

            return $this;
        }

        $pages = $this->article->list(
            categoryId: $category->id,
            status: 'published',
            perPage: self::RECENT_LIMIT,
        );
        $articlePage = $pages[1];
        assert($articlePage instanceof Page);
        /** @var list<array<string, mixed>> $rows */
        $rows = $articlePage->data;
        $articles = $this->articleFactory->fromRows($rows);

        $this->body = [
            'category' => $category,
            'articles' => $articles,
        ];

        return $this;
    }
}
