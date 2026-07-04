<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake\Defer\Resource\App;

use BEAR\Defer\DeferInterface;
use BEAR\Resource\Method;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

/**
 * C2 `defer-conditional` example — manual conditional defer via DeferInterface.
 *
 * When the follow-up is conditional, inject DeferInterface and ResourceInterface
 * directly and call $defer->add() with a manually constructed request.
 */
class ConditionalArticle extends ResourceObject
{
    public function __construct(
        private readonly ArticleRepository $repo,
        private readonly ResourceInterface $resource,
        private readonly DeferInterface $defer,
    ) {
    }

    public function onPost(string $title, string $body, bool $publish = false): static
    {
        $id = $this->repo->save($title, $body);
        $this->code = 202;
        $this->body = ['id' => $id];

        if ($publish) {
            $request = $this->resource->newRequest(Method::POST, 'app://self/publish', ['id' => $id]);
            $this->defer->add($request);
        }

        return $this;
    }
}
