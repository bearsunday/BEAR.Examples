<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\Code;
use BEAR\Resource\Exception\ParameterException;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Auth\AdminUserInterface;
use MyVendor\Cms\Entity\Article as ArticleEntity;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Entity\Tag;
use MyVendor\Cms\Exception\ValidationException;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;

use function array_map;
use function array_values;
use function is_array;
use function trim;

/**
 * @property array{
 *     mode: 'create'|'edit',
 *     article: ArticleEntity|null,
 *     values: array<string, mixed>,
 *     errors: array<string, list<string>>,
 *     authors: list<Author>,
 *     categories: list<Category>,
 *     tags: list<Tag>,
 *     selectedTagIds: list<int>,
 *     saved: string|null,
 * }|array{message: string}|array{} $body
 */
class Article extends ResourceObject
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

    public function onGet(int|null $id = null, string|null $saved = null): static
    {
        $article = $id === null ? null : $this->article->item($id);
        if ($id !== null && $article === null) {
            $this->code = 404;
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        if ($article !== null && ! $this->owns($article)) {
            return $this->forbidden();
        }

        $this->body = $this->formBody($article, $this->valuesFromArticle($article), [], $saved);

        return $this;
    }

    /** @SuppressWarnings("PHPMD.ExcessiveParameterList") Resource parameters mirror the HTML form fields. */
    public function onPost(
        mixed $id = null,
        mixed $slug = '',
        mixed $title = '',
        mixed $body = '',
        mixed $authorId = null,
        mixed $categoryId = null,
        mixed $status = 'draft',
        mixed $excerpt = null,
        mixed $publishedAt = null,
        mixed $tagIds = [],
    ): static {
        $articleId = $this->intOrNull($id);
        $article = null;
        if ($articleId === null) {
            $authorId = $this->admin->authorId();
        }

        if ($articleId !== null) {
            $article = $this->article->item($articleId);
            if ($article === null) {
                $this->code = Code::NOT_FOUND;
                $this->body = ['message' => 'Article not found'];

                return $this;
            }

            if (! $this->owns($article)) {
                return $this->forbidden();
            }
        }

        $values = $this->normaliseValues($articleId, [
            'slug' => $slug,
            'title' => $title,
            'body' => $body,
            'authorId' => $authorId,
            'categoryId' => $categoryId,
            'status' => $status,
            'excerpt' => $excerpt,
            'publishedAt' => $publishedAt,
            'tagIds' => $tagIds,
        ]);

        try {
            return $articleId === null
                ? $this->createArticle($values)
                : $this->updateArticle($articleId, $values);
        } catch (ValidationException | ParameterException $e) {
            $errors = $e instanceof ValidationException
                ? $e->getErrors()
                : ['_global' => [$e->getMessage()]];
            $this->code = 422;
            $this->body = $this->formBody($article, $values, $errors, null);

            return $this;
        }
    }

    /** @param array<string, mixed> $values */
    private function createArticle(array $values): static
    {
        $created = $this->resource->post('app://self/article', $values);
        if ($created->code >= 400 || ! is_array($created->body) || ! isset($created->body['id'])) {
            return $this->writeFailure($created, 'Article create failed');
        }

        $createdId = (int) $created->body['id'];
        $this->redirect('/admin/article?id=' . $createdId . '&saved=created');

        return $this;
    }

    /** @param array<string, mixed> $values */
    private function updateArticle(int $articleId, array $values): static
    {
        $values['id'] = $articleId;
        $updated = $this->resource->put('app://self/article', $values);
        if ($updated->code === 404) {
            $this->code = 404;
            $this->body = ['message' => 'Article not found'];

            return $this;
        }

        if ($updated->code >= 400) {
            return $this->writeFailure($updated, 'Article update failed');
        }

        $this->redirect('/admin/article?id=' . $articleId . '&saved=updated');

        return $this;
    }

    private function writeFailure(ResourceObject $ro, string $message): static
    {
        $this->code = $ro->code;
        $this->body = is_array($ro->body) ? $ro->body : ['message' => $message];

        return $this;
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     */
    private function normaliseValues(
        int|null $id,
        array $params,
    ): array {
        $values = [
            'slug' => trim((string) $params['slug']),
            'title' => trim((string) $params['title']),
            'body' => (string) $params['body'],
            'authorId' => (int) $params['authorId'],
            'categoryId' => (int) $params['categoryId'],
            'status' => trim((string) $params['status']),
            'excerpt' => $this->nullableString($params['excerpt']),
            'publishedAt' => $this->nullableString($params['publishedAt']),
            'tagIds' => $this->normaliseTagIds($params['tagIds']),
        ];

        if ($id !== null) {
            unset($values['slug'], $values['authorId'], $values['categoryId']);
        }

        return $values;
    }

    /**
     * @param array<string, mixed>        $values
     * @param array<string, list<string>> $errors
     *
     * @return array{
     *     mode: 'create'|'edit',
     *     article: ArticleEntity|null,
     *     values: array<string, mixed>,
     *     errors: array<string, list<string>>,
     *     authors: list<Author>,
     *     categories: list<Category>,
     *     tags: list<Tag>,
     *     selectedTagIds: list<int>,
     *     saved: string|null,
     * }
     */
    private function formBody(
        ArticleEntity|null $article,
        array $values,
        array $errors,
        string|null $saved,
    ): array {
        return [
            'mode' => $article === null ? 'create' : 'edit',
            'article' => $article,
            'values' => $values,
            'errors' => $errors,
            'authors' => $this->author->list(),
            'categories' => $this->category->list(),
            'tags' => $this->tag->list(),
            'selectedTagIds' => $this->normaliseTagIds($values['tagIds'] ?? []),
            'saved' => $saved,
        ];
    }

    /** @return array<string, mixed> */
    private function valuesFromArticle(ArticleEntity|null $article): array
    {
        if ($article === null) {
            return [
                'slug' => '',
                'title' => '',
                'body' => '',
                'authorId' => null,
                'categoryId' => null,
                'status' => 'draft',
                'excerpt' => null,
                'publishedAt' => null,
                'tagIds' => [],
            ];
        }

        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'body' => $article->body,
            'authorId' => $article->authorId,
            'categoryId' => $article->categoryId,
            'status' => $article->status->value,
            'excerpt' => $article->excerpt,
            'publishedAt' => $article->publishedAt,
            'tagIds' => array_map(static fn ($tag) => $tag->id, $this->tag->listByArticle($article->id)),
        ];
    }

    private function nullableString(mixed $value): string|null
    {
        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }

    private function intOrNull(mixed $value): int|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /** @return list<int> */
    private function normaliseTagIds(mixed $tagIds): array
    {
        if ($tagIds === null || $tagIds === '') {
            return [];
        }

        if (! is_array($tagIds)) {
            return [(int) $tagIds];
        }

        /** @var list<int> $normalised */
        $normalised = array_values(array_map(static fn ($id) => (int) $id, $tagIds));

        return $normalised;
    }

    private function redirect(string $location): void
    {
        $this->code = 303;
        $this->headers['Location'] = $location;
        $this->body = [];
    }

    private function owns(ArticleEntity $article): bool
    {
        return $article->authorId === $this->admin->authorId();
    }

    private function forbidden(): static
    {
        $this->code = Code::FORBIDDEN;
        $this->body = ['message' => 'Forbidden'];

        return $this;
    }
}
