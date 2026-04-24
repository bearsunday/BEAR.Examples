<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\AuthorQueryInterface;

class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $authorQuery,
    ) {
    }

    #[Link(rel: 'articles', href: 'app://self/articles')]
    public function onGet(int $id): static
    {
        $author = $this->authorQuery->get($id);
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
