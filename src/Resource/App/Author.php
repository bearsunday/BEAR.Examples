<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Command\AuthorCommandInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;

class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $authorQuery,
        private readonly AuthorCommandInterface $authorCommand,
    ) {
    }

    #[Link(rel: 'articles', href: 'app://self/articles')]
    public function onGet(int $id): static
    {
        $author = $this->authorQuery->getById($id);
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

    public function onPost(string $name, string $email, string $bio = ''): static
    {
        $this->authorCommand->add(name: $name, email: $email, bio: $bio);
        $created = $this->authorQuery->getByEmail($email);
        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/author?id=' . $created->id : '/author';
        $this->body = ['id' => $created?->id, 'email' => $email];

        return $this;
    }

    public function onPut(int $id, string $name, string $email, string $bio = ''): static
    {
        if ($this->authorQuery->getById($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $id];

            return $this;
        }

        $this->authorCommand->update(id: $id, name: $name, email: $email, bio: $bio);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }
}
