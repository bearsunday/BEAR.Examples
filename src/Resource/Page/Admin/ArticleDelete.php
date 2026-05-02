<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Query\ArticleQueryInterface;

use function is_array;

/** @property array{message: string}|array{article: Article}|array{} $body */
class ArticleDelete extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly ArticleQueryInterface $article,
    ) {
    }

    public function onGet(int $id): static
    {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = 404;
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        $this->body = ['article' => $article];

        return $this;
    }

    public function onPost(int $id): static
    {
        $deleted = $this->resource->delete('app://self/article', ['id' => $id]);
        if ($deleted->code === 404) {
            $this->code = 404;
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        if ($deleted->code >= 400) {
            $this->code = $deleted->code;
            $this->body = is_array($deleted->body) ? $deleted->body : ['message' => 'Article delete failed'];

            return $this;
        }

        $this->code = 303;
        $this->headers['Location'] = '/admin/articlelist?deleted=1';
        $this->body = [];

        return $this;
    }
}
