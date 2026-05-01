<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\Tag as TagEntity;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;

/** @property array{message: string}|array{tag: TagEntity, articles: list<Article>} $body */
class Tag extends ResourceObject
{
    private const int RECENT_LIMIT = 10;

    public function __construct(
        private readonly TagQueryInterface $tag,
        private readonly ArticleQueryInterface $article,
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

        $this->body = [
            'tag' => $tag,
            'articles' => $this->article->list(
                tagId: $tag->id,
                status: 'published',
                limit: self::RECENT_LIMIT,
            ),
        ];

        return $this;
    }
}
