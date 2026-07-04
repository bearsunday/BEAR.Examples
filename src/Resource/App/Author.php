<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Kata\Query\AuthorCommandInterface;
use BEAR\Kata\Query\AuthorQueryInterface;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;

use function assert;

#[Alps('Author')]
class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
        private readonly AuthorCommandInterface $authorCmd,
    ) {
    }

    #[Alps('goAuthor')]
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

    #[Alps('doCreateAuthor')]
    #[JsonSchema(schema: 'write_response.json', params: 'author_create.json')]
    public function onPost(string $name, string $email, string $bio = ''): static
    {
        $this->authorCmd->add($name, $email, $bio);
        // byEmail after add is invariant per docs/conventions.md §4.
        $created = $this->author->byEmail($email);
        assert($created !== null);
        $this->code = Code::CREATED;
        $this->headers['Location'] = '/author?id=' . $created->id;
        $this->body = ['id' => $created->id, 'email' => $email];

        return $this;
    }

    #[Alps('doUpdateAuthor')]
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
