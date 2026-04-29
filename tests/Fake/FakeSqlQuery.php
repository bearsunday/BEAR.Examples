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
 * In-memory fake SqlQueryInterface backed by var/fake/*.json.
 *
 * Lets the whole Read + Write stack run end-to-end without a real database.
 * Write ops update the in-memory tables and are also recorded to execLog
 * so tests can assert what was issued.
 *
 * PHPMD suppressions are intentional: this class implements a wide vendor
 * interface (`SqlQueryInterface` has many parameters per method, only some
 * meaningful for an in-memory fake) and routes every `#[DbQuery]` SQL id
 * through a single `mutate()` switch — a flat dispatch is easier to read
 * than a polymorphic split for a teaching fake.
 *
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
 */
final class FakeSqlQuery implements SqlQueryInterface
{
    /**
     * Write SQL ids handled by `mutate()`. An allowlist (rather than a regex
     * over verb suffixes) avoids false-positive dispatch if a future read
     * sqlId happens to end in `_link` / `_clear` / etc.
     */
    private const array WRITE_SQL_IDS = [
        'article_add',
        'article_update',
        'article_delete',
        'category_add',
        'category_update',
        'category_delete',
        'tag_add',
        'tag_delete',
        'author_add',
        'author_update',
        'media_add',
        'media_delete',
        'article_tag_clear',
        'article_tag_link',
    ];

    /** @var array<string, list<array<string, mixed>>> */
    private array $tables;

    /** @var list<array{sqlId: string, values: array<string, mixed>, insertedId?: int}> */
    public array $execLog = [];

    /** @var array<string, int> */
    private array $nextId;

    public function __construct(string|null $fakeDir = null)
    {
        $fakeDir ??= dirname(__DIR__, 2) . '/var/fake';
        $this->tables = [
            'article' => $this->load($fakeDir . '/article.json'),
            'category' => $this->load($fakeDir . '/category.json'),
            'tag' => $this->load($fakeDir . '/tag.json'),
            'author' => $this->load($fakeDir . '/author.json'),
            'media' => $this->load($fakeDir . '/media.json'),
            'articleTag' => $this->load($fakeDir . '/articleTag.json'),
        ];
        $this->nextId = [];
        foreach (['article', 'category', 'tag', 'author', 'media'] as $t) {
            $this->nextId[$t] = $this->maxId($t) + 1;
        }
    }

    /** @return list<array<string, mixed>> */
    private function load(string $path): array
    {
        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return $rows;
    }

    private function maxId(string $table): int
    {
        $max = 0;
        foreach ($this->tables[$table] as $row) {
            if ((int) $row['id'] <= $max) {
                continue;
            }

            $max = (int) $row['id'];
        }

        return $max;
    }

    // -- SqlQueryInterface ---------------------------------------------------

    /** @param array<string, mixed> $values */
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): object|null
    {
        if (in_array($sqlId, self::WRITE_SQL_IDS, true)) {
            // DbQueryInterceptor routes every #[DbQuery] method through getRow/getRowList
            // based on the return type. Writes (create_/update_/delete_) come through
            // here as well, so dispatch to the mutation handler and return null.
            $this->mutate($sqlId, $values);

            return null;
        }

        return match ($sqlId) {
            'article_item' => $this->findArticleById((int) $values['id']),
            'article_by_slug' => $this->findArticleBySlug((string) $values['slug']),
            'category_item' => $this->findCategoryById((int) $values['id']),
            'category_by_slug' => $this->findCategoryBySlug((string) $values['slug']),
            'tag_item' => $this->findTagById((int) $values['id']),
            'tag_by_slug' => $this->findTagBySlug((string) $values['slug']),
            'author_item' => $this->findAuthorById((int) $values['id']),
            'author_by_email' => $this->findAuthorByEmail((string) $values['email']),
            'media_item' => $this->findMediaById((int) $values['id']),
            'media_by_filename' => $this->findMediaByFilename((string) $values['filename']),
            default => throw new LogicException("FakeSqlQuery: unknown row sqlId '{$sqlId}'"),
        };
    }

    /**
     * The interface declares `array<array<mixed>>` for tabular fetches, but
     * with the DbQueryInterceptor + FetchNewInstance path used here the
     * actual return is a list of entities. The override is intentional.
     *
     * @param array<string, mixed> $values
     *
     * @return list<object>
     */
    public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array
    {
        if (in_array($sqlId, self::WRITE_SQL_IDS, true)) {
            $this->mutate($sqlId, $values);

            return [];
        }

        return match ($sqlId) {
            'article_list' => $this->listArticles($values),
            'category_list' => array_map(fn ($r) => $this->toCategory($r), $this->tables['category']),
            'tag_list' => array_map(fn ($r) => $this->toTag($r), $this->tables['tag']),
            'tag_list_by_article' => $this->listTagsByArticle((int) $values['articleId']),
            default => throw new LogicException("FakeSqlQuery: unknown row_list sqlId '{$sqlId}'"),
        };
    }

    /** @param array<string, mixed> $values */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void
    {
        $this->mutate($sqlId, $values);
    }

    /** @param array<string, mixed> $values */
    private function mutate(string $sqlId, array $values): void
    {
        $this->execLog[] = ['sqlId' => $sqlId, 'values' => $values];
        $logIdx = count($this->execLog) - 1;

        switch ($sqlId) {
            case 'article_add':
                $id = $this->nextId['article']++;
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
                $this->execLog[$logIdx]['insertedId'] = $id;

                return;

            case 'article_update':
                $this->updateRow('article', (int) $values['id'], [
                    'title' => $values['title'],
                    'body' => $values['body'],
                    'excerpt' => $values['excerpt'] ?? null,
                    'status' => $values['status'],
                    'publishedAt' => $values['publishedAt'] ?? null,
                ]);

                return;

            case 'article_delete':
                $this->deleteRow('article', (int) $values['id']);

                return;

            case 'category_add':
                $id = $this->nextId['category']++;
                $this->tables['category'][] = [
                    'id' => $id,
                    'slug' => $values['slug'],
                    'name' => $values['name'],
                    'description' => $values['description'] ?? null,
                    'parentId' => $values['parentId'] ?? null,
                ];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return;

            case 'category_update':
                $this->updateRow('category', (int) $values['id'], [
                    'name' => $values['name'],
                    'description' => $values['description'] ?? null,
                    'parentId' => $values['parentId'] ?? null,
                ]);

                return;

            case 'category_delete':
                $this->deleteRow('category', (int) $values['id']);

                return;

            case 'tag_add':
                $id = $this->nextId['tag']++;
                $this->tables['tag'][] = ['id' => $id, 'slug' => $values['slug'], 'name' => $values['name']];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return;

            case 'tag_delete':
                $this->deleteRow('tag', (int) $values['id']);

                return;

            case 'author_add':
                $id = $this->nextId['author']++;
                $this->tables['author'][] = [
                    'id' => $id,
                    'name' => $values['name'],
                    'email' => $values['email'],
                    'bio' => $values['bio'] ?? '',
                ];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return;

            case 'author_update':
                $this->updateRow('author', (int) $values['id'], [
                    'name' => $values['name'],
                    'email' => $values['email'],
                    'bio' => $values['bio'] ?? '',
                ]);

                return;

            case 'media_add':
                $id = $this->nextId['media']++;
                $this->tables['media'][] = [
                    'id' => $id,
                    'filename' => $values['filename'],
                    'mimeType' => $values['mimeType'],
                    'url' => $values['url'],
                    'alt' => $values['alt'] ?? null,
                    'width' => (int) ($values['width'] ?? 0),
                    'height' => (int) ($values['height'] ?? 0),
                ];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return;

            case 'media_delete':
                $this->deleteRow('media', (int) $values['id']);

                return;

            case 'article_tag_clear':
                $aid = (int) $values['articleId'];
                $this->tables['articleTag'] = array_values(array_filter(
                    $this->tables['articleTag'],
                    static fn ($r) => (int) $r['articleId'] !== $aid,
                ));

                return;

            case 'article_tag_link':
                $this->tables['articleTag'][] = [
                    'articleId' => (int) $values['articleId'],
                    'tagId' => (int) $values['tagId'],
                ];

                return;

            default:
                throw new LogicException("FakeSqlQuery: unknown exec sqlId '{$sqlId}'");
        }
    }

    /** @param array<string, mixed> $values */
    public function getCount(string $sqlId, array $values): int
    {
        return match ($sqlId) {
            'article_count' => count($this->filteredArticles($values)),
            default => throw new LogicException("FakeSqlQuery: unknown count sqlId '{$sqlId}'"),
        };
    }

    /** @param array<string, mixed> $values */
    public function getPages(string $sqlId, array $values, int $perPage, string $queryTemplate = '/{?page}', string|null $entity = null): PagesInterface
    {
        throw new LogicException('FakeSqlQuery does not support Pager/PagesInterface; use list/count directly.');
    }

    // -- helpers -------------------------------------------------------------

    /** @param array<string, mixed> $patch */
    private function updateRow(string $table, int $id, array $patch): void
    {
        foreach ($this->tables[$table] as $idx => $row) {
            if ((int) $row['id'] !== $id) {
                continue;
            }

            foreach ($patch as $k => $v) {
                $this->tables[$table][$idx][$k] = $v;
            }

            return;
        }
    }

    private function deleteRow(string $table, int $id): void
    {
        $this->tables[$table] = array_values(array_filter(
            $this->tables[$table],
            static fn ($r) => (int) $r['id'] !== $id,
        ));
    }

    private function findArticleById(int $id): Article|null
    {
        foreach ($this->tables['article'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toArticle($r);
            }
        }

        return null;
    }

    private function findArticleBySlug(string $slug): Article|null
    {
        foreach ($this->tables['article'] as $r) {
            if ((string) $r['slug'] === $slug) {
                return $this->toArticle($r);
            }
        }

        return null;
    }

    private function findCategoryById(int $id): Category|null
    {
        foreach ($this->tables['category'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toCategory($r);
            }
        }

        return null;
    }

    private function findCategoryBySlug(string $slug): Category|null
    {
        foreach ($this->tables['category'] as $r) {
            if ((string) $r['slug'] === $slug) {
                return $this->toCategory($r);
            }
        }

        return null;
    }

    private function findTagById(int $id): Tag|null
    {
        foreach ($this->tables['tag'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toTag($r);
            }
        }

        return null;
    }

    private function findTagBySlug(string $slug): Tag|null
    {
        foreach ($this->tables['tag'] as $r) {
            if ((string) $r['slug'] === $slug) {
                return $this->toTag($r);
            }
        }

        return null;
    }

    private function findAuthorById(int $id): Author|null
    {
        foreach ($this->tables['author'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toAuthor($r);
            }
        }

        return null;
    }

    private function findAuthorByEmail(string $email): Author|null
    {
        foreach ($this->tables['author'] as $r) {
            if ((string) $r['email'] === $email) {
                return $this->toAuthor($r);
            }
        }

        return null;
    }

    private function findMediaById(int $id): Media|null
    {
        foreach ($this->tables['media'] as $r) {
            if ((int) $r['id'] === $id) {
                return $this->toMedia($r);
            }
        }

        return null;
    }

    private function findMediaByFilename(string $filename): Media|null
    {
        foreach ($this->tables['media'] as $r) {
            if ((string) $r['filename'] === $filename) {
                return $this->toMedia($r);
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return list<array<string, mixed>>
     */
    private function filteredArticles(array $values): array
    {
        $rows = $this->tables['article'];
        if (isset($values['categoryId'])) {
            $cat = (int) $values['categoryId'];
            $rows = array_filter($rows, static fn ($r) => (int) $r['categoryId'] === $cat);
        }

        if (isset($values['status'])) {
            $s = (string) $values['status'];
            $rows = array_filter($rows, static fn ($r) => (string) $r['status'] === $s);
        }

        if (isset($values['tagId'])) {
            $tagId = (int) $values['tagId'];
            $articleIds = [];
            foreach ($this->tables['articleTag'] as $at) {
                if ((int) $at['tagId'] !== $tagId) {
                    continue;
                }

                $articleIds[] = (int) $at['articleId'];
            }

            $rows = array_filter($rows, static fn ($r) => in_array((int) $r['id'], $articleIds, true));
        }

        return array_values($rows);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return list<Article>
     */
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
            if ((int) $at['articleId'] !== $articleId) {
                continue;
            }

            $tagIds[] = (int) $at['tagId'];
        }

        $tags = array_filter($this->tables['tag'], static fn ($r) => in_array((int) $r['id'], $tagIds, true));

        return array_map(fn ($r) => $this->toTag($r), array_values($tags));
    }

    /** @param array<string, mixed> $r */
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

    /** @param array<string, mixed> $r */
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

    /** @param array<string, mixed> $r */
    private function toTag(array $r): Tag
    {
        return new Tag(id: (int) $r['id'], slug: (string) $r['slug'], name: (string) $r['name']);
    }

    /** @param array<string, mixed> $r */
    private function toAuthor(array $r): Author
    {
        return new Author(
            id: (int) $r['id'],
            name: (string) $r['name'],
            email: (string) $r['email'],
            bio: (string) ($r['bio'] ?? ''),
        );
    }

    /** @param array<string, mixed> $r */
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
