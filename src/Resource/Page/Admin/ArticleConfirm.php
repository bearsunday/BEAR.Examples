<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Attribute\SameOrigin;
use MyVendor\Cms\Auth\AdminUserInterface;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;

use function is_array;

/**
 * Publish-confirmation Page resource.
 *
 * GET renders the draft article in a read-only preview with an author /
 * category / tag summary so the editor can verify the rendered shape before
 * committing. POST forwards the transition to `app://self/article-publish`
 * and redirects to the public article on success.
 *
 * Modelled as its own Page resource (rather than a query-string mode on
 * `Page/Admin/Article`) so the URL is the state — `/admin/article/confirm?id=N`
 * is a bookmarkable preview, and the form-edit page stays focused on field
 * editing. This is the meeting outcome from Issue #37: separating the
 * confirmation surface keeps sensitive draft data off the edit form's
 * shared template and gives the publish flow a URI to point at.
 *
 * @property array{
 *     article: Article,
 *     authorName: string,
 *     categoryName: string,
 *     tagNames: list<string>,
 *     alreadyPublished: bool,
 *     errors: list<string>,
 * }|array{message: string}|array{} $body
 */
class ArticleConfirm extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly AdminUserInterface $admin,
        private readonly ArticleQueryInterface $article,
        private readonly AuthorQueryInterface $author,
        private readonly CategoryQueryInterface $category,
        private readonly TagQueryInterface $tag,
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

        if (! $this->owns($article)) {
            return $this->forbidden();
        }

        $this->body = $this->previewBody($article, [], $article->isPublished());

        return $this;
    }

    #[SameOrigin]
    public function onPost(int $id): static
    {
        $article = $this->article->item($id);
        if ($article === null) {
            $this->code = 404;
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        if (! $this->owns($article)) {
            return $this->forbidden();
        }

        $result = $this->resource->post('app://self/article-publish', ['id' => $id]);

        if ($result->code === 409) {
            // Article moved to published between GET and POST — render the
            // confirm page back, surface the conflict, and let the editor
            // navigate forward to the live article.
            $message = is_array($result->body) && isset($result->body['message'])
                ? (string) $result->body['message']
                : 'Article is already published';
            $this->code = 409;
            $this->body = $this->previewBody($article, [$message], true);

            return $this;
        }

        if ($result->code >= 400) {
            $message = is_array($result->body) && isset($result->body['message'])
                ? (string) $result->body['message']
                : 'Publish failed';
            $this->code = $result->code;
            $this->body = $this->previewBody($article, [$message], false);

            return $this;
        }

        $this->code = 303;
        $this->headers['Location'] = '/article?id=' . $id;
        $this->body = [];

        return $this;
    }

    private function owns(Article $article): bool
    {
        return $article->authorId === $this->admin->authorId();
    }

    private function forbidden(): static
    {
        $this->code = Code::FORBIDDEN;
        $this->body = ['message' => 'Forbidden'];

        return $this;
    }

    /**
     * @param list<string> $errors
     *
     * @return array{
     *     article: Article,
     *     authorName: string,
     *     categoryName: string,
     *     tagNames: list<string>,
     *     alreadyPublished: bool,
     *     errors: list<string>,
     * }
     */
    private function previewBody(Article $article, array $errors, bool $alreadyPublished): array
    {
        $authorEntity = $this->author->item($article->authorId);
        $categoryEntity = $this->category->item($article->categoryId);
        $tagNames = [];
        foreach ($this->tag->listByArticle($article->id) as $tag) {
            $tagNames[] = $tag->name;
        }

        return [
            'article' => $article,
            'authorName' => $authorEntity === null ? '' : $authorEntity->name,
            'categoryName' => $categoryEntity === null ? '' : $categoryEntity->name,
            'tagNames' => $tagNames,
            'alreadyPublished' => $alreadyPublished,
            'errors' => $errors,
        ];
    }
}
