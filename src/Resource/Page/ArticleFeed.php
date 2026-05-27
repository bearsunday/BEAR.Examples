<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use DateTimeImmutable;
use MyVendor\Cms\Query\ArticleSelectionQueryInterface;
use MyVendor\Cms\Result\ArticleSelection;

/** @property array{articles: ArticleSelection, now: DateTimeImmutable} $body */
class ArticleFeed extends ResourceObject
{
    public function __construct(
        private readonly ArticleSelectionQueryInterface $article,
    ) {
    }

    public function onGet(): static
    {
        $this->body = [
            'articles' => $this->article->list(null),
            'now' => new DateTimeImmutable(),
        ];

        return $this;
    }
}
