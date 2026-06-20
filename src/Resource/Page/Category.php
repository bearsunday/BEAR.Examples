<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Entity\Article;
use BEAR\Examples\Entity\Category as CategoryEntity;
use BEAR\Examples\Factory\ArticleFactory;
use BEAR\Examples\Query\ArticleQueryInterface;
use BEAR\Examples\Query\CategoryQueryInterface;
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
