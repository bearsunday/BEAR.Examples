<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

use BEAR\Defer\Attribute\Defer;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;

/**
 * C1 `defer-resource-request` example — declarative defer with #[Defer] + #[Link].
 *
 * The resource returns 202 immediately. The #[Defer] interceptor resolves
 * the #[Link] hrefs against the body and enqueues them for post-response
 * execution.
 */
class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleRepository $repo,
    ) {
    }

    #[Defer(['publish', 'note'])]
    #[Link(rel: 'publish', href: 'app://self/publish{?id}', method: 'post')]
    #[Link(rel: 'note', href: 'app://self/note{?id}', method: 'post')]
    public function onPost(string $title, string $body): static
    {
        $id = $this->repo->save($title, $body);
        $this->code = 202;
        $this->body = ['id' => $id];

        return $this;
    }
}
