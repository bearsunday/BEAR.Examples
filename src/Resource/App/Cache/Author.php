<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App\Cache;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\RepositoryModule\Annotation\Cacheable;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BEAR\Kata\Query\AuthorCommandInterface;
use BEAR\Kata\Query\AuthorQueryInterface;

/**
 * Cache showcase leaf — fully automatic dependency tracking.
 *
 * No cache-related primitives appear in this class: the `#[Cacheable]`
 * attribute is the entire cache surface. The framework writes the self
 * Surrogate-Key tag, parents that `#[Embed]` this resource pick it up via
 * `EtagSetter::setCacheDependency`, and writes are auto-purged by
 * `RefreshSameCommand` (the default `Commands` registered for `#[Cacheable]`
 * classes). See `tests/Resource/App/Cache/AuthorCacheTest.php` for the
 * user-zero-code invariant enforced by reflection.
 */
#[Alps('Author')]
#[Cacheable]
class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
        private readonly AuthorCommandInterface $authorCmd,
    ) {
    }

    #[Alps('goCacheAuthor')]
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

    #[Alps('doUpdateCacheAuthor')]
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
