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
use MyVendor\Cms\Input\ArticleCreateInput;
use MyVendor\Cms\Input\ArticleUpdateInput;
use MyVendor\Cms\Query\ArticleCommandInterface;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\ArticleTagCommandInterface;
use Ray\InputQuery\Attribute\Input;

class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleCommandInterface $articleCmd,
        private readonly ArticleTagCommandInterface $articleTagCmd,
    ) {
    }

    #[Link(rel: 'goArticleList', href: 'app://self/articles')]
    #[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
    #[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
    #[Embed(rel: 'author', src: 'app://self/author')]
    #[Embed(rel: 'category', src: 'app://self/category')]
    #[Embed(rel: 'tagList', src: 'app://self/tags')]
    #[JsonSchema('article.json')]
    #[Cli(name: 'article-show', description: 'Show an article by id', output: 'title')]
    public function onGet(
        #[Option(shortName: 'i', description: 'Article id')]
        int $id,
    ): static {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->body['author']->addQuery(['id' => $article->authorId]);
        $this->body['category']->addQuery(['id' => $article->categoryId]);
        $this->body['tagList']->addQuery(['articleId' => $article->id]);

        $this->body += [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'body' => $article->body,
            'excerpt' => $article->excerpt,
            'status' => $article->status,
            'publishedAt' => $article->publishedAt,
            'authorId' => $article->authorId,
            'categoryId' => $article->categoryId,
        ];

        return $this;
    }

    /** TODO(input-query+json-schema): JsonSchema cannot validate Input DTO params today; revisit when integration lands. */
    public function onPost(#[Input] ArticleCreateInput $input,): static
    {
        $this->articleCmd->add(
            $input->slug,
            $input->title,
            $input->body,
            $input->excerpt,
            $input->status,
            $input->publishedAt,
            $input->authorId,
            $input->categoryId,
        );

        $created = $this->article->bySlug($input->slug);
        if ($created !== null && $input->tagIds !== []) {
            $this->syncTags($created->id, $input->tagIds);
        }

        $this->code = Code::CREATED;
        $this->headers['Location'] = $created !== null ? '/article?id=' . $created->id : '/article?slug=' . $input->slug;
        $this->body = [
            'id' => $created?->id,
            'slug' => $input->slug,
        ];

        return $this;
    }

    /** TODO(input-query+json-schema): JsonSchema cannot validate Input DTO params today; revisit when integration lands. */
    public function onPut(#[Input]
    ArticleUpdateInput $input,): static
    {
        if ($this->article->item($input->id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $input->id];

            return $this;
        }

        $this->articleCmd->update(
            $input->id,
            $input->title,
            $input->body,
            $input->excerpt,
            $input->status,
            $input->publishedAt,
        );

        if ($input->tagIds !== null) {
            $this->syncTags($input->id, $input->tagIds);
        }

        $this->code = Code::OK;
        $this->body = ['id' => $input->id];

        return $this;
    }

    public function onDelete(int $id): static
    {
        if ($this->article->item($id) === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['message' => 'Article not found', 'id' => $id];

            return $this;
        }

        $this->articleCmd->delete($id);
        $this->code = Code::NO_CONTENT;
        $this->body = [];

        return $this;
    }

    /**
     * Replace the article's tag links with the given tag id list.
     *
     * @param list<int> $tagIds
     */
    private function syncTags(int $articleId, array $tagIds): void
    {
        $this->articleTagCmd->clear($articleId);
        foreach ($tagIds as $tagId) {
            $this->articleTagCmd->link($articleId, $tagId);
        }
    }
}
