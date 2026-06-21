<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Kata\Entity\Author;
use BEAR\Kata\Query\AuthorQueryInterface;

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
