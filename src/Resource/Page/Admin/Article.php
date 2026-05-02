<?php

declare(strict_types=1);

namespace MyVendor\Cms\Resource\Page\Admin;

use BEAR\Resource\Exception\JsonSchemaException;
use BEAR\Resource\Exception\ParameterException;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article as ArticleEntity;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Entity\Tag;
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
 *     errors: list<string>,
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
            if ($articleId === null) {
                $created = $this->resource->post('app://self/article', $values);
                if ($created->code >= 400 || ! is_array($created->body) || ! isset($created->body['id'])) {
                    $this->code = $created->code;
                    $this->body = is_array($created->body) ? $created->body : ['message' => 'Article create failed'];

                    return $this;
                }

                $createdId = (int) $created->body['id'];
                $this->redirect('/admin/article?id=' . $createdId . '&saved=created');

                return $this;
            }

            $values['id'] = $articleId;
            $updated = $this->resource->put('app://self/article', $values);
            if ($updated->code === 404) {
                $this->code = 404;
                $this->body = ['message' => 'Article not found'];

                return $this;
            }

            if ($updated->code >= 400) {
                $this->code = $updated->code;
                $this->body = is_array($updated->body) ? $updated->body : ['message' => 'Article update failed'];

                return $this;
            }

            $this->redirect('/admin/article?id=' . $articleId . '&saved=updated');

            return $this;
        } catch (JsonSchemaException | ParameterException $e) {
            $article = $articleId === null ? null : $this->article->item($articleId);
            $this->code = 422;
            $this->body = $this->formBody($article, $values, [$e->getMessage()], null);

            return $this;
        }
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
     * @param array<string, mixed> $values
     * @param list<string>         $errors
     *
     * @return array{
     *     mode: 'create'|'edit',
     *     article: ArticleEntity|null,
     *     values: array<string, mixed>,
     *     errors: list<string>,
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
            'selectedTagIds' => $this->selectedTagIds($article, $values),
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

    /**
     * @param array<string, mixed> $values
     *
     * @return list<int>
     */
    private function selectedTagIds(ArticleEntity|null $article, array $values): array
    {
        if (isset($values['tagIds']) && is_array($values['tagIds'])) {
            /** @var list<int> $tagIds */
            $tagIds = array_values(array_map(static fn ($id) => (int) $id, $values['tagIds']));

            return $tagIds;
        }

        if ($article === null) {
            return [];
        }

        /** @var list<int> $tagIds */
        $tagIds = array_map(static fn ($tag) => $tag->id, $this->tag->listByArticle($article->id));

        return $tagIds;
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
}
