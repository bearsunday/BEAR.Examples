<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Entity\Author;
use BEAR\Examples\Query\AuthorQueryInterface;

/** @property array{authors: list<Author>} $body */
class AuthorList extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    public function onGet(): static
    {
        $this->body = ['authors' => $this->author->list()];

        return $this;
    }
}
