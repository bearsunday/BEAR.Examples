<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\AuthorCommandInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;

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
    #[Cli(name: 'author-show', description: 'Show an author by id', output: 'name')]
    public function onGet(
        #[Option(shortName: 'i', description: 'Author id')]
        int $id,
    ): static {
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
    #[Cli(name: 'author-add', description: 'Create a new author')]
    public function onPost(
        #[Option(shortName: 'n', description: 'Author name')]
        string $name,
        #[Option(shortName: 'e', description: 'Author email')]
        string $email,
        #[Option(shortName: 'b', description: 'Author bio')]
        string $bio = '',
    ): static {
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
    #[Cli(name: 'author-update', description: 'Update an existing author')]
    public function onPut(
        #[Option(shortName: 'i', description: 'Author id')]
        int $id,
        #[Option(shortName: 'n', description: 'Author name')]
        string $name,
        #[Option(shortName: 'e', description: 'Author email')]
        string $email,
        #[Option(shortName: 'b', description: 'Author bio')]
        string $bio = '',
    ): static {
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
