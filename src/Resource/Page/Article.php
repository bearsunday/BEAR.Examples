<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page;

use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article as ArticleEntity;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;

class Article extends ResourceObject
{
    /** @var array{message: string}|array{article: ArticleEntity, author: Author|null, category: Category|null, tags: list<array{id: int, slug: string, name: string}>} */
    public $body;

    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly AuthorQueryInterface $author,
        private readonly CategoryQueryInterface $category,
        private readonly TagQueryInterface $tag,
    ) {
    }

    public function onGet(int $id): static
    {
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';

        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = 404;
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        $this->body = [
            'article' => $article,
            'author' => $this->author->item($article->authorId),
            'category' => $this->category->item($article->categoryId),
            'tags' => array_map(static fn ($tag) => [
                'id' => $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->name,
            ], $this->tag->listByArticle($article->id)),
        ];

        return $this;
    }
}
