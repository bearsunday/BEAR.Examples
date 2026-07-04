<?php

declare(strict_types=1);

namespace BEAR\Kata\Resource\App;

use BEAR\ApiDoc\Annotation\Alps;
use BEAR\Cli\Attribute\Cli;
use BEAR\Cli\Attribute\Option;
use BEAR\Kata\Input\ArticleCreateInput;
use BEAR\Kata\Input\ArticleUpdateInput;
use BEAR\Kata\Query\ArticleCommandInterface;
use BEAR\Kata\Query\ArticleQueryInterface;
use BEAR\Kata\Query\ArticleTagCommandInterface;
use BEAR\Kata\Service\SqlDateTime;
use BEAR\RepositoryModule\Annotation\Purge;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use Ray\InputQuery\Attribute\Input;

use function assert;

#[Alps('Article')]
class Article extends ResourceObject
{
    public function __construct(
        private readonly ArticleQueryInterface $article,
        private readonly ArticleCommandInterface $articleCmd,
        private readonly ArticleTagCommandInterface $articleTagCmd,
        private readonly SqlDateTime $sqlDateTime,
    ) {
    }

    #[Alps('goArticle')]
    #[Link(rel: 'goArticleList', href: 'app://self/articles')]
    #[Link(rel: 'goAuthor', href: 'app://self/author{?id}')]
    #[Link(rel: 'goCategory', href: 'app://self/category{?id}')]
    #[Embed(rel: 'author', src: 'app://self/author')]
    #[Embed(rel: 'category', src: 'app://self/category')]
    #[Embed(rel: 'tagList', src: 'app://self/tags')]
    #[JsonSchema('article.json')]
    #[Cli(name: 'article-show', description: 'Show an article by id', output: 'title')]
    public function onGet(#[Option(shortName: 'i', description: 'Article id')] int $id): static
    {
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
            'status' => $article->status->value,
            'publishedAt' => $article->publishedAt,
            'authorId' => $article->authorId,
            'categoryId' => $article->categoryId,
        ];

        return $this;
    }

    #[Alps('doCreateArticle')]
    #[JsonSchema(schema: 'write_response.json', params: 'article_create.json')]
    #[Purge(uri: 'app://self/articles')]
    public function onPost(#[Input] ArticleCreateInput $input): static
    {
        $this->articleCmd->add(
            $input->slug,
            $input->title,
            $input->body,
            $input->excerpt,
            $input->status,
            $this->sqlDateTime->fromRfc3339($input->publishedAt),
            $input->authorId,
            $input->categoryId,
        );

        // Per docs/conventions.md §4 "After-INSERT id": bySlug after add is the
        // canonical id-recovery path and is treated as invariant. Failure here
        // means a unique-key constraint or replica-lag anomaly — let it surface
        // as a 5xx rather than silently dropping tagIds.
        $created = $this->article->bySlug($input->slug);
        assert($created !== null);
        if ($input->tagIds !== []) {
            $this->syncTags($created->id, $input->tagIds);
        }

        $this->code = Code::CREATED;
        $this->headers['Location'] = '/article?id=' . $created->id;
        $this->body = [
            'id' => $created->id,
            'slug' => $input->slug,
        ];

        return $this;
    }

    #[Alps('doUpdateArticle')]
    #[JsonSchema(schema: 'write_response.json', params: 'article_update.json')]
    #[Purge(uri: 'app://self/articles')]
    public function onPut(#[Input] ArticleUpdateInput $input): static
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
            $this->sqlDateTime->fromRfc3339($input->publishedAt),
        );

        if ($input->tagIds !== null) {
            $this->syncTags($input->id, $input->tagIds);
        }

        $this->code = Code::OK;
        $this->body = ['id' => $input->id];

        return $this;
    }

    #[Alps('doDeleteArticle')]
    #[Purge(uri: 'app://self/articles')]
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
