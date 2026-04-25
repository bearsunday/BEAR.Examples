<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\App;

use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Query\ArticleCommandInterface;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\ArticleTagCommandInterface;

class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $articleQuery,
        private readonly ArticleCommandInterface $articleCommand,
        private readonly ArticleTagCommandInterface $articleTagCommand,
    ) {
    }

    /**
     * Replace the article's tag links with the given tag id list.
     *
     * @param list<int> $tagIds
     */
    private function syncTags(int $articleId, array $tagIds): void
    {
        $this->articleTagCommand->clear($articleId);
        foreach ($tagIds as $tagId) {
            $this->articleTagCommand->link($articleId, $tagId);
        }
    }

    #[Link(rel: 'goArticleList', href: 'app://self/articles')]
    #[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
    #[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
    #[Embed(rel: 'goAuthor', src: 'app://self/author')]
    #[Embed(rel: 'goCategory', src: 'app://self/category')]
    #[Embed(rel: 'goTagList', src: 'app://self/tags')]
    #[JsonSchema('article.json')]
    #[Cli(name: 'article-show', description: 'Show an article by id', output: 'title')]
    public function onGet(
        #[Option(shortName: 'i', description: 'Article id')]
        int $id,
    ): static {
        $article = $this->articleQuery->getById($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        // Inject runtime values into the embedded resource Requests.
        // The Embed interceptor has already populated $this->body['goAuthor'/...]
        // with Request objects; addQuery() supplements their query string before
        // they materialize at render time.
        $this->body['goAuthor']->addQuery(['id' => $article->authorId]);
        $this->body['goCategory']->addQuery(['id' => $article->categoryId]);
        $this->body['goTagList']->addQuery(['articleId' => $article->id]);

        // Article's own data fields.
        $this->body['id'] = $article->id;
        $this->body['slug'] = $article->slug;
        $this->body['title'] = $article->title;
        $this->body['body'] = $article->body;
        $this->body['excerpt'] = $article->excerpt;
        $this->body['status'] = $article->status;
        $this->body['publishedAt'] = $article->publishedAt;
        $this->body['authorId'] = $article->authorId;
        $this->body['categoryId'] = $article->categoryId;

        return $this;
    }

    /** @param list<int> $tagIds Optional list of tag ids to link to the new article. */
    public function onPost(
        string $slug,
        string $title,
        string $body,
        int $authorId,
        int $categoryId,
        string|null $excerpt = null,
        string $status = 'draft',
        string|null $publishedAt = null,
        array $tagIds = [],
    ): static {
        $this->articleCommand->add(
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
        if ($created !== null && $tagIds !== []) {
            $this->syncTags($created->id, $tagIds);
        }

        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/article?id=' . $created->id : '/article?slug=' . $slug;
        $this->body = [
            'id' => $created?->id,
            'slug' => $slug,
        ];

        return $this;
    }

    /** @param list<int>|null $tagIds When provided, replaces the tag set entirely. */
    public function onPut(
        int $id,
        string $title,
        string $body,
        string $status,
        string|null $excerpt = null,
        string|null $publishedAt = null,
        array|null $tagIds = null,
    ): static {
        if ($this->articleQuery->getById($id) === null) {
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

        if ($tagIds !== null) {
            $this->syncTags($id, $tagIds);
        }

        $this->code = Code::OK;
        $this->body = ['id' => $id];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->articleQuery->getById($id) === null) {
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
