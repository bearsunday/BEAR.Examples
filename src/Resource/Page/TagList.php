<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Entity\Tag;
use BEAR\Examples\Query\TagQueryInterface;

/** @property array{tags: list<Tag>} $body */
class TagList extends ResourceObject
{
    public function __construct(
        private readonly TagQueryInterface $tag,
    ) {
    }

    public function onGet(): static
    {
        $this->body = ['tags' => $this->tag->list()];

        return $this;
    }
}
