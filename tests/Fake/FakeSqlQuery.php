<?php

declare(strict_types=1);

namespace BEAR\Kata\Fake;

use Aura\Sql\ExtendedPdo;
use BEAR\Kata\Entity\Article;
use BEAR\Kata\Entity\ArticleStatus;
use BEAR\Kata\Entity\AuthIdentity;
use BEAR\Kata\Entity\Author;
use BEAR\Kata\Entity\Category;
use BEAR\Kata\Entity\Media;
use BEAR\Kata\Entity\Tag;
use Ray\MediaQuery\Exception\LogicException;
use Ray\MediaQuery\FetchAssoc;
use Ray\MediaQuery\FetchInterface;
use Ray\MediaQuery\PagesInterface;
use Ray\MediaQuery\Result\AffectedRows;
use Ray\MediaQuery\Result\InsertedRow;
use Ray\MediaQuery\Result\PostQueryContext;
use Ray\MediaQuery\Result\PostQueryInterface;
use Ray\MediaQuery\SqlQueryInterface;

use function array_filter;
use function array_flip;
use function array_map;
use function array_values;
use function count;
use function dirname;
use function file_get_contents;
use function in_array;
use function is_array;
use function json_decode;
use function str_contains;
use function str_replace;
use function strcmp;
use function usort;

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
        'article_publish',
        'article_delete',
        'category_add',
        'category_update',
        'category_delete',
        'tag_add',
        'tag_update',
        'tag_delete',
        'author_add',
        'author_update',
        'auth_identity_add',
        'media_add',
        'media_delete',
        'article_tag_clear',
        'article_tag_link',
    ];

    /** @var array<string, list<array<string, mixed>>> */
    private array $tables;

    /** @var list<array{sqlId: string, values: array<string, mixed>, insertedId?: int}> */
    public array $execLog = [];

    /** @var list<array{method: string, sqlId: string, values: array<string, mixed>}> */
    public array $queryLog = [];

    /** @var array<string, int> */
    private array $nextId;

    public function __construct(
        string|null $fakeDir = null,
    ) {
        $fakeDir ??= dirname(__DIR__, 2) . '/var/fake';
        $this->tables = [
            'article' => $this->load($fakeDir . '/article.json'),
            'category' => $this->load($fakeDir . '/category.json'),
            'tag' => $this->load($fakeDir . '/tag.json'),
            'author' => $this->load($fakeDir . '/author.json'),
            'authIdentity' => $this->load($fakeDir . '/authIdentity.json'),
            'media' => $this->load($fakeDir . '/media.json'),
            'articleTag' => $this->load($fakeDir . '/articleTag.json'),
        ];
        $this->nextId = [];
        foreach (['article', 'category', 'tag', 'author', 'authIdentity', 'media'] as $t) {
            $this->nextId[$t] = $this->maxId($t) + 1;
        }
    }

    public function resetExecLog(): void
    {
        $this->execLog = [];
    }

    public function resetQueryLog(): void
    {
        $this->queryLog = [];
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
    public function getRow(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array|object|null
    {
        if (in_array($sqlId, self::WRITE_SQL_IDS, true)) {
            // DbQueryInterceptor routes every #[DbQuery] method through getRow/getRowList
            // based on the return type. Writes (create_/update_/delete_) come through
            // here as well, so dispatch to the mutation handler and return null.
            $this->mutate($sqlId, $values);

            return null;
        }

        $this->queryLog[] = ['method' => 'getRow', 'sqlId' => $sqlId, 'values' => $values];

        return match ($sqlId) {
            'article_item' => $this->findArticleById((int) $values['id']),
            'article_by_slug' => $this->findArticleBySlug((string) $values['slug']),
            'article_as_array_item' => $this->findArticleRowById((int) $values['id']),
            'article_sqlquery_item' => $this->findArticleRowById((int) $values['id']),
            'article_sqlquery_previous' => $this->findPreviousArticleRow((string) $values['publishedAt'], (int) $values['id']),
            'article_sqlquery_next' => $this->findNextArticleRow((string) $values['publishedAt'], (int) $values['id']),
            'category_item' => $this->findCategoryById((int) $values['id']),
            'category_by_slug' => $this->findCategoryBySlug((string) $values['slug']),
            'tag_item' => $this->findTagById((int) $values['id']),
            'tag_by_slug' => $this->findTagBySlug((string) $values['slug']),
            'author_item' => $this->findAuthorById((int) $values['id']),
            'author_by_email' => $this->findAuthorByEmail((string) $values['email']),
            'auth_identity_by_provider_subject' => $this->findAuthIdentity(
                (string) $values['provider'],
                (string) $values['subject'],
            ),
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
     * @return list<array<string, mixed>|object>
     */
    public function getRowList(string $sqlId, array $values = [], FetchInterface|null $fetch = null): array
    {
        if (in_array($sqlId, self::WRITE_SQL_IDS, true)) {
            $this->mutate($sqlId, $values);

            return [];
        }

        $this->queryLog[] = ['method' => 'getRowList', 'sqlId' => $sqlId, 'values' => $values];

        return match ($sqlId) {
            'article_list' => $this->listArticles($values),
            'category_list' => array_map(fn ($r) => $this->toCategory($r), $this->tables['category']),
            'tag_list' => array_map(fn ($r) => $this->toTag($r), $this->tables['tag']),
            'tag_list_by_article' => $this->listTagsByArticle((int) $values['articleId']),
            'tag_list_by_articles' => $this->listTagRowsByArticles($values['articleIds']),
            'author_list' => array_map(fn ($r) => $this->toAuthor($r), $this->tables['author']),
            default => throw new LogicException("FakeSqlQuery: unknown row_list sqlId '{$sqlId}'"),
        };
    }

    /** @param array<string, mixed> $values */
    public function exec(string $sqlId, array $values = [], FetchInterface|null $fetch = null): void
    {
        $this->mutate($sqlId, $values);
    }

    /**
     * @param array<string, mixed>             $values
     * @param class-string<PostQueryInterface> $postQueryClass
     */
    public function execPostQuery(
        string $sqlId,
        array $values,
        string $postQueryClass,
        FetchInterface|null $fetch = null,
    ): PostQueryInterface {
        if (! in_array($sqlId, self::WRITE_SQL_IDS, true)) {
            return $postQueryClass::fromContext($this->postQueryContext(
                $values,
                $this->selectRows($sqlId, $values, $fetch),
            ));
        }

        $result = $this->mutate($sqlId, $values);

        return match ($postQueryClass) {
            InsertedRow::class => new InsertedRow(
                $values,
                isset($result['insertedId']) ? (string) $result['insertedId'] : null,
            ),
            AffectedRows::class => new AffectedRows($result['affectedRows']),
            default => $postQueryClass::fromContext($this->postQueryContext($values, [])),
        };
    }

    /**
     * @param array<string, mixed> $values
     * @param array<mixed>         $rows
     */
    private function postQueryContext(array $values, array $rows): PostQueryContext
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $statement = $pdo->perform('SELECT 1');

        return new PostQueryContext($statement, $pdo, $values, $rows);
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return list<mixed>
     */
    private function selectRows(string $sqlId, array $values, FetchInterface|null $fetch): array
    {
        $assoc = $fetch === null || $fetch instanceof FetchAssoc;
        $this->queryLog[] = ['method' => 'selectRows', 'sqlId' => $sqlId, 'values' => $values];

        return match ($sqlId) {
            'article_list', 'article_selection_list' => $assoc
                ? array_map(fn ($r) => $this->toArticleSqlRow($r), $this->filteredArticles($values))
                : $this->listArticles($values),
            'category_list' => $assoc
                ? array_map(fn ($r) => $this->toCategorySqlRow($r), $this->tables['category'])
                : array_map(fn ($r) => $this->toCategory($r), $this->tables['category']),
            'tag_list' => $assoc
                ? array_map(fn ($r) => $this->toTagSqlRow($r), $this->tables['tag'])
                : array_map(fn ($r) => $this->toTag($r), $this->tables['tag']),
            'tag_list_by_article' => $assoc
                ? array_map(fn (Tag $tag) => $this->toTagSqlRow([
                    'id' => $tag->id,
                    'slug' => $tag->slug,
                    'name' => $tag->name,
                ]), $this->listTagsByArticle((int) $values['articleId']))
                : $this->listTagsByArticle((int) $values['articleId']),
            'tag_list_by_articles' => $this->listTagRowsByArticles($values['articleIds']),
            'author_list' => $assoc
                ? array_map(fn ($r) => $this->toAuthorSqlRow($r), $this->tables['author'])
                : array_map(fn ($r) => $this->toAuthor($r), $this->tables['author']),
            default => $this->selectSingleRow($sqlId, $values, $assoc),
        };
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return list<array<string, mixed>|object>
     */
    private function selectSingleRow(string $sqlId, array $values, bool $assoc): array
    {
        $row = $this->selectSingleValue($sqlId, $values, $assoc);

        return $row === null ? [] : [$row];
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, mixed>|object|null
     */
    private function selectSingleValue(string $sqlId, array $values, bool $assoc): array|object|null
    {
        return match ($sqlId) {
            'article_item' => $assoc
                ? $this->mapRawRow('article', 'id', (int) $values['id'], $this->toArticleSqlRow(...))
                : $this->findArticleById((int) $values['id']),
            'article_by_slug' => $assoc
                ? $this->mapRawRow('article', 'slug', (string) $values['slug'], $this->toArticleSqlRow(...))
                : $this->findArticleBySlug((string) $values['slug']),
            'article_as_array_item', 'article_sqlquery_item' => $this->findArticleRowById((int) $values['id']),
            'article_sqlquery_previous' => $this->findPreviousArticleRow((string) $values['publishedAt'], (int) $values['id']),
            'article_sqlquery_next' => $this->findNextArticleRow((string) $values['publishedAt'], (int) $values['id']),
            'category_item' => $assoc
                ? $this->mapRawRow('category', 'id', (int) $values['id'], $this->toCategorySqlRow(...))
                : $this->findCategoryById((int) $values['id']),
            'category_by_slug' => $assoc
                ? $this->mapRawRow('category', 'slug', (string) $values['slug'], $this->toCategorySqlRow(...))
                : $this->findCategoryBySlug((string) $values['slug']),
            'tag_item' => $assoc
                ? $this->mapRawRow('tag', 'id', (int) $values['id'], $this->toTagSqlRow(...))
                : $this->findTagById((int) $values['id']),
            'tag_by_slug' => $assoc
                ? $this->mapRawRow('tag', 'slug', (string) $values['slug'], $this->toTagSqlRow(...))
                : $this->findTagBySlug((string) $values['slug']),
            'author_item' => $assoc
                ? $this->mapRawRow('author', 'id', (int) $values['id'], $this->toAuthorSqlRow(...))
                : $this->findAuthorById((int) $values['id']),
            'author_by_email' => $assoc
                ? $this->mapRawRow('author', 'email', (string) $values['email'], $this->toAuthorSqlRow(...))
                : $this->findAuthorByEmail((string) $values['email']),
            'auth_identity_by_provider_subject' => $assoc
                ? $this->toAuthIdentitySqlRow($this->findAuthIdentityRow(
                    (string) $values['provider'],
                    (string) $values['subject'],
                ))
                : $this->findAuthIdentity((string) $values['provider'], (string) $values['subject']),
            'media_item' => $assoc
                ? $this->mapRawRow('media', 'id', (int) $values['id'], $this->toMediaSqlRow(...))
                : $this->findMediaById((int) $values['id']),
            'media_by_filename' => $assoc
                ? $this->mapRawRow('media', 'filename', (string) $values['filename'], $this->toMediaSqlRow(...))
                : $this->findMediaByFilename((string) $values['filename']),
            default => throw new LogicException("FakeSqlQuery: unknown post query select sqlId '{$sqlId}'"),
        };
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $map
     *
     * @return array<string, mixed>|null
     */
    private function mapRawRow(string $table, string $field, int|string $value, callable $map): array|null
    {
        foreach ($this->tables[$table] as $row) {
            if ((string) $row[$field] !== (string) $value) {
                continue;
            }

            return $map($row);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array{affectedRows: int, insertedId?: int}
     */
    private function mutate(string $sqlId, array $values): array
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

                return ['affectedRows' => 1, 'insertedId' => $id];

            case 'article_update':
                return [
                    'affectedRows' => $this->updateRow(
                        'article',
                        (int) $values['id'],
                        [
                            'title' => $values['title'],
                            'body' => $values['body'],
                            'excerpt' => $values['excerpt'] ?? null,
                            'status' => $values['status'],
                            'publishedAt' => $values['publishedAt'] ?? null,
                        ],
                    ),
                ];

            case 'article_publish':
                $id = (int) $values['id'];
                foreach ($this->tables['article'] as $idx => $row) {
                    if ((int) $row['id'] !== $id || $row['status'] !== 'draft') {
                        continue;
                    }

                    $this->tables['article'][$idx] = [
                        ...$row,
                        'status' => $values['status'],
                        'publishedAt' => $values['publishedAt'],
                    ];

                    return ['affectedRows' => 1];
                }

                return ['affectedRows' => 0];

            case 'article_delete':
                return ['affectedRows' => $this->deleteRow('article', (int) $values['id'])];

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

                return ['affectedRows' => 1, 'insertedId' => $id];

            case 'category_update':
                return [
                    'affectedRows' => $this->updateRow(
                        'category',
                        (int) $values['id'],
                        [
                            'name' => $values['name'],
                            'description' => $values['description'] ?? null,
                            'parentId' => $values['parentId'] ?? null,
                        ],
                    ),
                ];

            case 'category_delete':
                return ['affectedRows' => $this->deleteRow('category', (int) $values['id'])];

            case 'tag_add':
                $id = $this->nextId['tag']++;
                $this->tables['tag'][] = ['id' => $id, 'slug' => $values['slug'], 'name' => $values['name']];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return ['affectedRows' => 1, 'insertedId' => $id];

            case 'tag_update':
                return [
                    'affectedRows' => $this->updateRow(
                        'tag',
                        (int) $values['id'],
                        [
                            'slug' => $values['slug'],
                            'name' => $values['name'],
                        ],
                    ),
                ];

            case 'tag_delete':
                return ['affectedRows' => $this->deleteRow('tag', (int) $values['id'])];

            case 'author_add':
                $id = $this->nextId['author']++;
                $this->tables['author'][] = [
                    'id' => $id,
                    'name' => $values['name'],
                    'email' => $values['email'],
                    'bio' => $values['bio'] ?? '',
                ];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return ['affectedRows' => 1, 'insertedId' => $id];

            case 'author_update':
                return [
                    'affectedRows' => $this->updateRow(
                        'author',
                        (int) $values['id'],
                        [
                            'name' => $values['name'],
                            'email' => $values['email'],
                            'bio' => $values['bio'] ?? '',
                        ],
                    ),
                ];

            case 'auth_identity_add':
                $id = $this->nextId['authIdentity']++;
                $this->tables['authIdentity'][] = [
                    'id' => $id,
                    'provider' => $values['provider'],
                    'subject' => $values['subject'],
                    'authorId' => (int) $values['authorId'],
                    'email' => $values['email'],
                    'name' => $values['name'],
                ];
                $this->execLog[$logIdx]['insertedId'] = $id;

                return ['affectedRows' => 1, 'insertedId' => $id];

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

                return ['affectedRows' => 1, 'insertedId' => $id];

            case 'media_delete':
                return ['affectedRows' => $this->deleteRow('media', (int) $values['id'])];

            case 'article_tag_clear':
                $aid = (int) $values['articleId'];
                $before = count($this->tables['articleTag']);
                $this->tables['articleTag'] = array_values(array_filter(
                    $this->tables['articleTag'],
                    static fn ($r) => (int) $r['articleId'] !== $aid,
                ));

                return ['affectedRows' => $before - count($this->tables['articleTag'])];

            case 'article_tag_link':
                $this->tables['articleTag'][] = [
                    'articleId' => (int) $values['articleId'],
                    'tagId' => (int) $values['tagId'],
                ];

                return ['affectedRows' => 1];

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
        unset($entity);
        $this->queryLog[] = ['method' => 'getPages', 'sqlId' => $sqlId, 'values' => $values];

        return match ($sqlId) {
            'article_list' => new FakePages(
                array_map(fn ($r) => $this->toArticleSqlRow($r), $this->filteredArticles($values)),
                $perPage,
                $queryTemplate,
            ),
            default => throw new LogicException("FakeSqlQuery: unknown pages sqlId '{$sqlId}'"),
        };
    }

    // -- helpers -------------------------------------------------------------

    /** @param array<string, mixed> $patch */
    private function updateRow(string $table, int $id, array $patch): int
    {
        foreach ($this->tables[$table] as $idx => $row) {
            if ((int) $row['id'] !== $id) {
                continue;
            }

            foreach ($patch as $k => $v) {
                $this->tables[$table][$idx][$k] = $v;
            }

            return 1;
        }

        return 0;
    }

    private function deleteRow(string $table, int $id): int
    {
        $before = count($this->tables[$table]);
        $this->tables[$table] = array_values(array_filter(
            $this->tables[$table],
            static fn ($r) => (int) $r['id'] !== $id,
        ));

        return $before - count($this->tables[$table]);
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

    /** @return array<string, mixed>|null */
    private function findArticleRowById(int $id): array|null
    {
        foreach ($this->tables['article'] as $r) {
            if ((int) $r['id'] === $id) {
                return $r;
            }
        }

        return null;
    }

    /** @return array<string, mixed>|null */
    private function findPreviousArticleRow(string $publishedAt, int $id): array|null
    {
        $best = null;
        foreach ($this->tables['article'] as $r) {
            if ((string) $r['status'] !== ArticleStatus::Published->value || empty($r['publishedAt'])) {
                continue;
            }

            $candidatePublishedAt = (string) $r['publishedAt'];
            $candidateId = (int) $r['id'];
            if ($candidatePublishedAt > $publishedAt || ($candidatePublishedAt === $publishedAt && $candidateId >= $id)) {
                continue;
            }

            $isBetter = $best === null
                || (string) $best['publishedAt'] < $candidatePublishedAt
                || ((string) $best['publishedAt'] === $candidatePublishedAt && (int) $best['id'] < $candidateId);
            if (! $isBetter) {
                continue;
            }

            $best = $r;
        }

        return $best;
    }

    /** @return array<string, mixed>|null */
    private function findNextArticleRow(string $publishedAt, int $id): array|null
    {
        $best = null;
        foreach ($this->tables['article'] as $r) {
            if ((string) $r['status'] !== ArticleStatus::Published->value || empty($r['publishedAt'])) {
                continue;
            }

            $candidatePublishedAt = (string) $r['publishedAt'];
            $candidateId = (int) $r['id'];
            if ($candidatePublishedAt < $publishedAt || ($candidatePublishedAt === $publishedAt && $candidateId <= $id)) {
                continue;
            }

            $isBetter = $best === null
                || (string) $best['publishedAt'] > $candidatePublishedAt
                || ((string) $best['publishedAt'] === $candidatePublishedAt && (int) $best['id'] > $candidateId);
            if (! $isBetter) {
                continue;
            }

            $best = $r;
        }

        return $best;
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

    private function findAuthIdentity(string $provider, string $subject): AuthIdentity|null
    {
        $row = $this->findAuthIdentityRow($provider, $subject);

        return $row === null ? null : $this->toAuthIdentity($row);
    }

    /** @return array<string, mixed>|null */
    private function findAuthIdentityRow(string $provider, string $subject): array|null
    {
        foreach ($this->tables['authIdentity'] as $r) {
            if ((string) $r['provider'] === $provider && (string) $r['subject'] === $subject) {
                return $r;
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

        if (isset($values['authorId'])) {
            $au = (int) $values['authorId'];
            $rows = array_filter($rows, static fn ($r) => (int) $r['authorId'] === $au);
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

        $rows = array_values($rows);
        usort($rows, static function (array $a, array $b): int {
            $published = strcmp((string) ($b['publishedAt'] ?? ''), (string) ($a['publishedAt'] ?? ''));

            return $published !== 0 ? $published : (int) $b['id'] <=> (int) $a['id'];
        });

        return $rows;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return list<Article>
     */
    private function listArticles(array $values): array
    {
        return array_map(fn ($r) => $this->toArticle($r), $this->filteredArticles($values));
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

    /** @return list<array{articleId: int, id: int, slug: string, name: string}> */
    private function listTagRowsByArticles(mixed $articleIds): array
    {
        if (! is_array($articleIds)) {
            return [];
        }

        $wanted = array_flip(array_map(
            static fn (mixed $articleId): int => (int) $articleId,
            $articleIds,
        ));
        $rows = [];
        foreach ($this->tables['articleTag'] as $link) {
            $articleId = (int) $link['articleId'];
            if (! isset($wanted[$articleId])) {
                continue;
            }

            $tag = $this->findTagById((int) $link['tagId']);
            if ($tag === null) {
                continue;
            }

            $rows[] = [
                'articleId' => $articleId,
                'id' => $tag->id,
                'slug' => $tag->slug,
                'name' => $tag->name,
            ];
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => [$a['articleId'], $a['name']] <=> [$b['articleId'], $b['name']],
        );

        return $rows;
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array{
     *     id: int,
     *     slug: string,
     *     title: string,
     *     body: string,
     *     excerpt: string|null,
     *     status: string,
     *     published_at: mixed,
     *     author_id: int,
     *     category_id: int
     * }
     */
    private function toArticleSqlRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'title' => (string) $r['title'],
            'body' => (string) $r['body'],
            'excerpt' => isset($r['excerpt']) ? (string) $r['excerpt'] : null,
            'status' => (string) $r['status'],
            'published_at' => $r['publishedAt'] ?? null,
            'author_id' => (int) $r['authorId'],
            'category_id' => (int) $r['categoryId'],
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array{id: int, slug: string, name: string, description: string|null, parent_id: int|null}
     */
    private function toCategorySqlRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'name' => (string) $r['name'],
            'description' => isset($r['description']) ? (string) $r['description'] : null,
            'parent_id' => isset($r['parentId']) ? (int) $r['parentId'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array{id: int, slug: string, name: string}
     */
    private function toTagSqlRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'name' => (string) $r['name'],
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array{id: int, name: string, email: string, bio: string}
     */
    private function toAuthorSqlRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'email' => (string) $r['email'],
            'bio' => (string) ($r['bio'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed>|null $r
     *
     * @return array{id: int, provider: string, subject: string, author_id: int, email: string, name: string}|null
     */
    private function toAuthIdentitySqlRow(array|null $r): array|null
    {
        if ($r === null) {
            return null;
        }

        return [
            'id' => (int) $r['id'],
            'provider' => (string) $r['provider'],
            'subject' => (string) $r['subject'],
            'author_id' => (int) $r['authorId'],
            'email' => (string) $r['email'],
            'name' => (string) $r['name'],
        ];
    }

    /**
     * @param array<string, mixed> $r
     *
     * @return array{id: int, filename: string, mime_type: string, url: string, alt: string|null, width: int, height: int}
     */
    private function toMediaSqlRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'filename' => (string) $r['filename'],
            'mime_type' => (string) $r['mimeType'],
            'url' => (string) $r['url'],
            'alt' => isset($r['alt']) ? (string) $r['alt'] : null,
            'width' => (int) $r['width'],
            'height' => (int) $r['height'],
        ];
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
            status: ArticleStatus::from((string) $r['status']),
            publishedAt: $this->normaliseDateTime($r['publishedAt'] ?? null),
            authorId: (int) $r['authorId'],
            categoryId: (int) $r['categoryId'],
        );
    }

    private function normaliseDateTime(mixed $value): string|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $string = (string) $value;
        if (str_contains($string, 'T')) {
            return $string;
        }

        return str_replace(' ', 'T', $string) . 'Z';
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
    private function toAuthIdentity(array $r): AuthIdentity
    {
        return new AuthIdentity(
            id: (int) $r['id'],
            provider: (string) $r['provider'],
            subject: (string) $r['subject'],
            authorId: (int) $r['authorId'],
            email: (string) $r['email'],
            name: (string) $r['name'],
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
