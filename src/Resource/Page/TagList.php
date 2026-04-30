<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Tag;
use MyVendor\Cms\Query\TagQueryInterface;

class TagList extends ResourceObject
{
    /** @var array{tags: list<Tag>} */
    public $body;

    public function __construct(
        private readonly TagQueryInterface $tag,
    ) {
    }

    public function onGet(): static
    {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = ['tags' => $this->tag->list()];

        return $this;
    }
}
