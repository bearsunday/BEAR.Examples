<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\ArticleQueryInterface;

use function array_map;

class Articles extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $articleQuery,
    ) {
    }

    #[Link(rel: 'goArticle', href: 'app://self/article{?id}')]
    public function onGet(
        int $page = 1,
        int $perPage = 20,
        int|null $categoryId = null,
        int|null $tagId = null,
        string|null $status = null,
    ): static {
        $page = $page < 1 ? 1 : $page;
        $perPage = $perPage < 1 ? 20 : ($perPage > 100 ? 100 : $perPage);
        $offset = ($page - 1) * $perPage;

        $items = $this->articleQuery->list(
            categoryId: $categoryId,
            tagId: $tagId,
            status: $status,
            limit: $perPage,
            offset: $offset,
        );

        $this->body = [
            'items' => array_map(static fn ($a) => [
                'id' => $a->id,
                'slug' => $a->slug,
                'title' => $a->title,
                'excerpt' => $a->excerpt,
                'status' => $a->status,
                'publishedAt' => $a->publishedAt,
                'authorId' => $a->authorId,
                'categoryId' => $a->categoryId,
            ], $items),
            'page' => $page,
            'perPage' => $perPage,
            'count' => count($items),
        ];

        return $this;
    }
}
