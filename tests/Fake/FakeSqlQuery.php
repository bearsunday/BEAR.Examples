<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Entity\Media;
use MyVendor\Cms\Entity\Tag;
use Ray\MediaQuery\Exception\LogicException;
use Ray\MediaQuery\FetchInterface;
use Ray\MediaQuery\PagesInterface;
use Ray\MediaQuery\SqlQueryInterface;

use function array_filter;
use function array_map;
use function array_slice;
use function array_values;
use function count;
use function dirname;
use function file_get_contents;
use function in_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/**
 * In-memory fake implementation of SqlQueryInterface.
 *
 * Dispatches on the SQL id and returns entities constructed from
 * var/fake/data-50.*.json, letting Resource/Query code run end-to-end
 * before a real database is available. Write ops (exec) are recorded.
 */
final class FakeSqlQuery implements SqlQueryInterface
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $tables;

    /** @var list<array{sqlId: string, values: array<string, mixed>}> */
    public array $execLog = [];

    private int $nextArticleId;
    private int $nextCategoryId;
    private int $nextTagId;
    private int $nextAuthorId;
    private int $nextMediaId;

    public function __construct(string|null $fakeDir = null)
    {
        $fakeDir ??= dirname(__DIR__, 2) . '/var/fake';
        $this->tables = [
            'article' => $this->load($fakeDir . '/data-50.article.json'),
            'category' => $this->load($fakeDir . '/data-50.category.json'),
            'tag' => $this->load($fakeDir . '/data-50.tag.json'),
            'author' => $this->load($fakeDir . '/data-50.author.json'),
            'media' => $this->load($fakeDir . '/data-50.media.json'),
            'articleTag' => $this->load($fakeDir . '/data-50.articleTag.json'),
        ];
        $this->nextArticleId = $this->nextId('article');
        $this->nextCategoryId = $this->nextId('category');
        $this->nextTagId = $this->nextId('tag');
        $this->nextAuthorId = $this->nextId('author');
        $this->nextMediaId = $this->nextId('media');
    }

    /** @return list<array<string, mixed>> */
    private function load(string $path): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $rows;
    }

    private function nextId(string $table): int
    {
        $max = 0;
        foreach ($this->tables[$table] as $row) {
            if ($row['id'] > $max) {
                $max = (int) $row['id'];
            }
        }

        return $max + 1;
    }

    /** {@inheritDoc} */
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): object|null
    {
        return match ($sqlId) {
            'get_article' => $this->findArticle((int) $values['id']),
            'get_category' => $this->findCategory((int) $values['id']),
            'get_tag' => $this->findTag((int) $values['id']),
            'get_author' => $this->findAuthor((int) $values['id']),
            'get_media' => $this->findMedia((int) $values['id']),
            default => throw new LogicException("FakeSqlQuery: unknown row sqlId '{$sqlId}'"),
        };
    }

    /** {@inheritDoc} */
    public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array
    {
        return match ($sqlId) {
            'list_articles' => $this->listArticles($values),
            'list_categories' => array_map(fn ($r) => $this->toCategory($r), $this->tables['category']),
            'list_tags' => array_map(fn ($r) => $this->toTag($r), $this->tables['tag']),
            'list_tags_by_article' => $this->listTagsByArticle((int) $values['articleId']),
            default => throw new LogicException("FakeSqlQuery: unknown row_list sqlId '{$sqlId}'"),
        };
    }

    /** {@inheritDoc} */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void
    {
        $this->execLog[] = ['sqlId' => $sqlId, 'values' => $values];

        switch ($sqlId) {
            case 'create_article':
                $id = $this->nextArticleId++;
                $this->tables['article'][] = [
                    'id' => $id,
                    'slug' => $values['slug'],
                    'title' => $values['title'],
                    'body' => $values['body'],
                    'excerpt' => $values['excerpt'] ?? null,
                    'status' => $values['status'],
                    'publishedAt' => $values['publishedAt'] ?? null,
                    'authorId' => (int) $values['authorId'],
                    'categoryId' => (int) $values['categoryId'],
                ];
                $this->execLog[count($this->execLog) - 1]['insertedId'] = $id;

                return;
            case 'update_article':
                foreach ($this->tables['article'] as &$r) {
                    if ($r['id'] === (int) $values['id']) {
                        $r['title'] = $values['title'];
                        $r['body'] = $values['body'];
                        if (isset($values['excerpt'])) {
                            $r['excerpt'] = $values['excerpt'];
                        }

                        $r['status'] = $values['status'];

                        return;
                    }
                }

                return;
            case 'delete_article':
                $this->tables['article'] = array_values(array_filter($this->tables['article'], static fn ($r) => $r['id'] !== (int) $values['id']));

                return;
            default:
                // Other writes (category, tag, author, media) can be implemented on demand.
                return;
        }
    }

    /** {@inheritDoc} */
    public function getCount(string $sqlId, array $values): int
    {
        return match ($sqlId) {
            'count_articles' => count($this->filteredArticles($values)),
            default => throw new LogicException("FakeSqlQuery: unknown count sqlId '{$sqlId}'"),
        };
    }

    /** {@inheritDoc} */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface
    {
        throw new LogicException('FakeSqlQuery does not support Pager/PagesInterface; use list/count directly.');
    }

    // -- Read helpers -----------------------------------------------------

    private function findArticle(int $id): Article|null
    {
        foreach ($this->tables['article'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toArticle($r);
            }
        }

        return null;
    }

    private function findCategory(int $id): Category|null
    {
        foreach ($this->tables['category'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toCategory($r);
            }
        }

        return null;
    }

    private function findTag(int $id): Tag|null
    {
        foreach ($this->tables['tag'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toTag($r);
            }
        }

        return null;
    }

    private function findAuthor(int $id): Author|null
    {
        foreach ($this->tables['author'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toAuthor($r);
            }
        }

        return null;
    }

    private function findMedia(int $id): Media|null
    {
        foreach ($this->tables['media'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toMedia($r);
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    private function filteredArticles(array $values): array
    {
        $rows = $this->tables['article'];
        if (isset($values['categoryId']) && $values['categoryId'] !== null) {
            $cat = (int) $values['categoryId'];
            $rows = array_filter($rows, static fn ($r) => (int) $r['categoryId'] === $cat);
        }

        if (isset($values['status']) && $values['status'] !== null) {
            $s = (string) $values['status'];
            $rows = array_filter($rows, static fn ($r) => (string) $r['status'] === $s);
        }

        if (isset($values['tagId']) && $values['tagId'] !== null) {
            $tagId = (int) $values['tagId'];
            $articleIds = [];
            foreach ($this->tables['articleTag'] as $at) {
                if ((int) $at['tagId'] === $tagId) {
                    $articleIds[] = (int) $at['articleId'];
                }
            }

            $rows = array_filter($rows, static fn ($r) => in_array((int) $r['id'], $articleIds, true));
        }

        return array_values($rows);
    }

    /** @return list<Article> */
    private function listArticles(array $values): array
    {
        $rows = $this->filteredArticles($values);
        $offset = isset($values['offset']) ? (int) $values['offset'] : 0;
        $limit = isset($values['limit']) ? (int) $values['limit'] : 20;
        $rows = array_slice($rows, $offset, $limit);

        return array_map(fn ($r) => $this->toArticle($r), $rows);
    }

    /** @return list<Tag> */
    private function listTagsByArticle(int $articleId): array
    {
        $tagIds = [];
        foreach ($this->tables['articleTag'] as $at) {
            if ((int) $at['articleId'] === $articleId) {
                $tagIds[] = (int) $at['tagId'];
            }
        }

        $tags = array_filter($this->tables['tag'], static fn ($r) => in_array((int) $r['id'], $tagIds, true));

        return array_map(fn ($r) => $this->toTag($r), array_values($tags));
    }

    private function toArticle(array $r): Article
    {
        return new Article(
            id: (int) $r['id'],
            slug: (string) $r['slug'],
            title: (string) $r['title'],
            body: (string) $r['body'],
            excerpt: isset($r['excerpt']) ? (string) $r['excerpt'] : null,
            status: (string) $r['status'],
            publishedAt: $r['publishedAt'] ?? null,
            authorId: (int) $r['authorId'],
            categoryId: (int) $r['categoryId'],
        );
    }

    private function toCategory(array $r): Category
    {
        return new Category(
            id: (int) $r['id'],
            slug: (string) $r['slug'],
            name: (string) $r['name'],
            description: isset($r['description']) ? (string) $r['description'] : null,
            parentId: isset($r['parentId']) ? (int) $r['parentId'] : null,
        );
    }

    private function toTag(array $r): Tag
    {
        return new Tag(id: (int) $r['id'], slug: (string) $r['slug'], name: (string) $r['name']);
    }

    private function toAuthor(array $r): Author
    {
        return new Author(
            id: (int) $r['id'],
            name: (string) $r['name'],
            email: (string) $r['email'],
            bio: (string) ($r['bio'] ?? ''),
        );
    }

    private function toMedia(array $r): Media
    {
        return new Media(
            id: (int) $r['id'],
            filename: (string) $r['filename'],
            mimeType: (string) $r['mimeType'],
            url: (string) $r['url'],
            alt: isset($r['alt']) ? (string) $r['alt'] : null,
            width: (int) $r['width'],
            height: (int) $r['height'],
        );
    }
}
