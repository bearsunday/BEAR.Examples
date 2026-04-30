<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\Category as CategoryEntity;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;

/** @property array{message: string}|array{category: CategoryEntity, articles: list<Article>} $body */
class Category extends ResourceObject
{
    private const int RECENT_LIMIT = 10;

    public function __construct(
        private readonly CategoryQueryInterface $category,
        private readonly ArticleQueryInterface $article,
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

        $this->body = [
            'category' => $category,
            'articles' => $this->article->list(
                categoryId: $category->id,
                status: 'published',
                limit: self::RECENT_LIMIT,
            ),
        ];

        return $this;
    }
}
