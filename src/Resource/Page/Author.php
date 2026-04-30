<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Author as AuthorEntity;
use MyVendor\Cms\Query\AuthorQueryInterface;

class Author extends ResourceObject
{
    /** @var array{message: string}|array{author: AuthorEntity} */
    public $body;

    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    public function onGet(int $id): static
    {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';

        $author = $this->author->item($id);
        if ($author === null) {
            $this->code = 404;
            $this->body = ['message' => 'Author not found'];

            return $this;
        }

        $this->body = ['author' => $author];

        return $this;
    }
}
