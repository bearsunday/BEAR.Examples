<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Kata\Entity\Article;
use BEAR\Kata\Entity\Tag as TagEntity;
use BEAR\Kata\Factory\ArticleFactory;
use BEAR\Kata\Query\ArticleQueryInterface;
use BEAR\Kata\Query\TagQueryInterface;
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
