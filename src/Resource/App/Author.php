<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\AuthorCommandInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;

class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
        private readonly AuthorCommandInterface $authorCmd,
    ) {
    }

    #[Link(rel: 'goArticleList', href: 'app://self/articles')]
    #[JsonSchema('author.json')]
    public function onGet(int $id): static
    {
        $author = $this->author->item($id);
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

    #[JsonSchema(schema: 'write_response.json', params: 'author_create.json')]
    public function onPost(string $name, string $email, string $bio = ''): static
    {
        $this->authorCmd->add($name, $email, $bio);
        $created = $this->author->byEmail($email);
        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/author?id=' . $created->id : '/author';
        $this->body = ['id' => $created?->id, 'email' => $email];

        return $this;
    }

    #[JsonSchema(schema: 'write_response.json', params: 'author_update.json')]
    public function onPut(int $id, string $name, string $email, string $bio = ''): static
    {
        if ($this->author->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $id];

            return $this;
        }

        $this->authorCmd->update($id, $name, $email, $bio);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }
}
