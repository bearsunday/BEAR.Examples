<?php

declare(strict_types=1);

namespace BEAR\Examples\Resource\Page;

use BEAR\Resource\ResourceObject;
use BEAR\Examples\Entity\Article as ArticleEntity;
use BEAR\Examples\Entity\Author;
use BEAR\Examples\Entity\Category;
use BEAR\Examples\Query\ArticleQueryInterface;
use BEAR\Examples\Query\AuthorQueryInterface;
use BEAR\Examples\Query\CategoryQueryInterface;
use BEAR\Examples\Query\TagQueryInterface;
use BEAR\Examples\Service\MarkdownRendererInterface;

use function array_map;

/** @property array{message: string}|array{article: ArticleEntity, bodyHtml: string, author: Author|null, category: Category|null, tags: list<array{id: int, slug: string, name: string}>} $body */
class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly AuthorQueryInterface $author,
        private readonly CategoryQueryInterface $category,
        private readonly TagQueryInterface $tag,
        private readonly MarkdownRendererInterface $markdown,
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

        $this->body = [
            'article' => $article,
            'bodyHtml' => $this->markdown->render($article->body),
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
