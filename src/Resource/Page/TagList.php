<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\Page;

use BEAR\Kata\Entity\Tag;
use BEAR\Kata\Query\TagQueryInterface;
use BEAR\Resource\ResourceObject;

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
