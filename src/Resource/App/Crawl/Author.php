<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Crawl;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Query\AuthorQueryInterface;

class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    #[JsonSchema('crawl_author.json')]
    #[Link(crawl: 'author-tree', rel: 'articleList', href: 'app://self/crawl/articles?authorId={id}')]
    public function onGet(int $id): static
    {
        $author = $this->author->item($id);
        if ($author === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $id];

            return $this;
        }

        $this->body = [
            'id' => $author->id,
            'name' => $author->name,
            'email' => $author->email,
            'bio' => $author->bio,
        ];

        return $this;
    }
}
