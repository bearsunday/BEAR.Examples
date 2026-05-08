<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\Tag as TagEntity;
use MyVendor\Cms\Factory\ArticleFactory;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;
use Ray\AuraSqlModule\Pagerfanta\Page;

use function assert;

/** @property array{message: string}|array{tag: TagEntity, articles: list<Article>} $body */
class Tag extends ResourceObject
{
    private const int RECENT_LIMIT = 10;

    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly ArticleQueryInterface $article,
        private readonly ArticleFactory $articleFactory,
    ) {
    }

    public function onGet(int $id): static
    {
        $tag = $this->tag->item($id);
        if ($tag === null) {
            $this->code = 404;
            $this->body = ['message' => 'Tag not found'];

            return $this;
        }

        $pages = $this->article->list(
            tagId: $tag->id,
            status: 'published',
            perPage: self::RECENT_LIMIT,
        );
        $articlePage = $pages[1];
        assert($articlePage instanceof Page);
        /** @var list<array<string, mixed>> $rows */
        $rows = $articlePage->data;
        $articles = $this->articleFactory->fromRows($rows);

        $this->body = [
            'tag' => $tag,
            'articles' => $articles,
        ];

        return $this;
    }
}
