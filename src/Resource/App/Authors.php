<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Kata\Query\AuthorQueryInterface;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

use function array_map;
use function count;

#[Alps('AuthorList')]
#[Cacheable]
class Authors extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
    ) {
    }

    #[Alps('goAuthorList')]
    #[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
    #[JsonSchema('authorList.json')]
    public function onGet(): static
    {
        $items = $this->author->list();
        $this->body = [
            'items' => array_map(static fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'bio' => $a->bio,
            ], $items),
            'count' => count($items),
        ];

        return $this;
    }
}
