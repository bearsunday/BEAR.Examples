<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Author as AuthorEntity;
use MyVendor\Cms\Query\AuthorQueryInterface;

/** @property array{message: string}|array{author: AuthorEntity} $body */
class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    public function onGet(int $id): static
    {
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
