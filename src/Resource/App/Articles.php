<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Factory\ArticleFactory;
use MyVendor\Cms\Query\ArticleQueryInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function array_map;
use function assert;
use function count;

#[Alps('ArticleList')]
class Articles extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
    ) {
    }

    #[Alps('goArticleList')]
    #[Link(rel: 'goArticle', href: 'app://self/article{?id}')]
    #[JsonSchema('articleList.json')]
    #[Cli(name: 'article-list', description: 'List articles (paginated, filterable)')]
    public function onGet(
        #[Option(shortName: 'p', description: 'Page number')]
        int $page = 1,
        #[Option(shortName: 'n', description: 'Items per page (max 100)')]
        int $perPage = 20,
        #[Option(shortName: 'c', description: 'Filter by category id')]
        int|null $categoryId = null,
        #[Option(shortName: 't', description: 'Filter by tag id')]
        int|null $tagId = null,
        #[Option(shortName: 'a', description: 'Filter by author id')]
        int|null $authorId = null,
        #[Option(shortName: 's', description: 'Filter by status (draft|published)')]
        string|null $status = null,
    ): static {
        $page = $page < 1 ? 1 : $page;
        $perPage = $perPage < 1 ? 20 : ($perPage > 100 ? 100 : $perPage);
        $pages = $this->article->list(
            categoryId: $categoryId,
            tagId: $tagId,
            authorId: $authorId,
            status: $status,
            perPage: $perPage,
        );
        $articlePage = $pages[$page];
        assert($articlePage instanceof Page);
        /** @var list<array<string, mixed>> $rows */
        $rows = $articlePage->data;
        $items = $this->articleFactory->fromRows($rows);

        $this->body = [
            'items' => array_map(static fn ($a) => [
                'id' => $a->id,
                'slug' => $a->slug,
                'title' => $a->title,
                'excerpt' => $a->excerpt,
                'status' => $a->status->value,
                'publishedAt' => $a->publishedAt,
                'authorId' => $a->authorId,
                'categoryId' => $a->categoryId,
            ], $items),
            'page' => $articlePage->current,
            'perPage' => $articlePage->maxPerPage,
            'count' => count($items),
            'totalCount' => $articlePage->total,
        ];

        return $this;
    }
}
