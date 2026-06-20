<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\App\Crawl;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\Examples\DataLoader\ArticleTagsDataLoader;
use BEAR\Examples\Factory\ArticleFactory;
use BEAR\Examples\Query\ArticleQueryInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function array_map;
use function assert;

class Articles extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
    ) {
    }

    #[Link(
        crawl: 'author-tree',
        rel: 'tagList',
        href: 'app://self/crawl/tags?articleId={id}',
        dataLoader: ArticleTagsDataLoader::class,
    )]
    #[JsonSchema('crawl_article_list.json')]
    public function onGet(int $authorId, int $perPage = 100): static
    {
        $pages = $this->article->list(authorId: $authorId, perPage: $perPage);
        $articlePage = $pages[1];
        assert($articlePage instanceof Page);
        /** @var list<array<string, mixed>> $rows */
        $rows = $articlePage->data;
        $items = $this->articleFactory->fromRows($rows);

        $this->body = array_map(static fn ($article): array => [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'status' => $article->status->value,
            'publishedAt' => $article->publishedAt,
            'authorId' => $article->authorId,
            'categoryId' => $article->categoryId,
        ], $items);

        return $this;
    }
}
