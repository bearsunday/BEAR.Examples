<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Entity\Author as AuthorEntity;
use BEAR\Examples\Query\AuthorQueryInterface;

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
