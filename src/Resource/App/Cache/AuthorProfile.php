<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App\Cache;

use BEAR\QueryRepository\Header;
use BEAR\QueryRepository\UriTagInterface;
use BEAR\RepositoryModule\Annotation\CacheableResponse;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use JsonException;

use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * Canonical cache dependency showcase.
 *
 * The dependency URI is fully determined by onGet() input, so the source code
 * and the runtime Surrogate-Key dependency describe the same relationship.
 */
#[CacheableResponse]
class AuthorProfile extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly UriTagInterface $uriTag,
    ) {
    }

    public function onGet(int $authorId): static
    {
        $dependencyUri = 'app://self/cache/author?id=' . $authorId;
        $author = $this->resource->get($dependencyUri);
        $authorBody = $this->authorBody($author);
        if ($author->code >= Code::BAD_REQUEST || $authorBody === null) {
            $this->code = $author->code;
            $this->body = ['message' => 'Author not found', 'id' => $authorId];

            return $this;
        }

        $this->headers[Header::SURROGATE_KEY] = $this->uriTag->fromAssoc(
            'app://self/cache/author{?id}',
            [['id' => $authorId]],
        );
        $this->body = [
            'authorId' => $authorId,
            'dependencyUri' => $dependencyUri,
            'cachePattern' => 'CacheableResponse + explicit URI tag dependency',
            '_embedded' => ['author' => $authorBody],
        ];

        return $this;
    }

    /** @return array<string, mixed>|null */
    private function authorBody(ResourceObject $author): array|null
    {
        try {
            $body = json_decode((string) $author, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($body)) {
            return null;
        }

        unset($body['_links']);

        return $body;
    }
}
