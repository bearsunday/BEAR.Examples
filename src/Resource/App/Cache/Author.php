<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\QueryRepository\DonutRepositoryInterface;
use BEAR\QueryRepository\Header;
use BEAR\QueryRepository\UriTagInterface;
use BEAR\RepositoryModule\Annotation\CacheableResponse;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\AuthorCommandInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;

/**
 * Cache showcase author resource.
 *
 * This intentionally mirrors the normal Author read shape, but stays under
 * app://self/cache/* so the reference cache demo does not change the main API
 * resource contract.
 */
#[CacheableResponse]
class Author extends ResourceObject
{
    public function __construct(
        private readonly AuthorQueryInterface $author,
        private readonly AuthorCommandInterface $authorCmd,
        private readonly DonutRepositoryInterface $repository,
        private readonly UriTagInterface $uriTag,
    ) {
    }

    public function onGet(int $id): static
    {
        $this->headers[Header::SURROGATE_KEY] = $this->authorTag($id);
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

    public function onPut(int $id, string $name, string $email, string $bio = ''): static
    {
        if ($this->author->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Author not found', 'id' => $id];

            return $this;
        }

        $this->authorCmd->update($id, $name, $email, $bio);
        $this->repository->invalidateTags([$this->authorTag($id)]);
        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }

    private function authorTag(int $id): string
    {
        return $this->uriTag->fromAssoc('app://self/cache/author{?id}', [['id' => $id]]);
    }
}
