<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Command\ArticleCommandInterface;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;

class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $articleQuery,
        private readonly AuthorQueryInterface $authorQuery,
        private readonly CategoryQueryInterface $categoryQuery,
        private readonly TagQueryInterface $tagQuery,
        private readonly ArticleCommandInterface $articleCommand,
    ) {
    }

    #[Link(rel: 'articles', href: 'app://self/articles')]
    #[Link(rel: 'author', href: 'app://self/author{?id}')]
    #[Link(rel: 'category', href: 'app://self/category{?id}')]
    public function onGet(int $id): static
    {
        $article = $this->articleQuery->get($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $author = $this->authorQuery->get($article->authorId);
        $category = $this->categoryQuery->get($article->categoryId);
        $tags = $this->tagQuery->listByArticle($article->id);

        $this->body = [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'body' => $article->body,
            'excerpt' => $article->excerpt,
            'status' => $article->status,
            'publishedAt' => $article->publishedAt,
            'authorId' => $article->authorId,
            'categoryId' => $article->categoryId,
            '_embedded' => [
                'author' => $author,
                'category' => $category,
                'tags' => $tags,
            ],
        ];

        return $this;
    }

    public function onPost(
        string $slug,
        string $title,
        string $body,
        int $authorId,
        int $categoryId,
        string|null $excerpt = null,
        string $status = 'draft',
        string|null $publishedAt = null,
    ): static {
        $this->articleCommand->create(
            slug: $slug,
            title: $title,
            body: $body,
            excerpt: $excerpt,
            status: $status,
            publishedAt: $publishedAt,
            authorId: $authorId,
            categoryId: $categoryId,
        );

        $created = $this->articleQuery->getBySlug($slug);
        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/article?id=' . $created->id : '/article?slug=' . $slug;
        $this->body = [
            'id' => $created?->id,
            'slug' => $slug,
        ];

        return $this;
    }

    public function onPut(
        int $id,
        string $title,
        string $body,
        string $status,
        string|null $excerpt = null,
        string|null $publishedAt = null,
    ): static {
        if ($this->articleQuery->get($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->articleCommand->update(
            id: $id,
            title: $title,
            body: $body,
            excerpt: $excerpt,
            status: $status,
            publishedAt: $publishedAt,
        );

        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->articleQuery->get($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->articleCommand->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }
}
