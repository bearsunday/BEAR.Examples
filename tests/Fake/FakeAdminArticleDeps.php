<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use BadMethodCallException;
use BEAR\Resource\Method;
use BEAR\Resource\RequestInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use MyVendor\Cms\Entity\Article;
use MyVendor\Cms\Entity\Author;
use MyVendor\Cms\Entity\Category;
use MyVendor\Cms\Entity\Tag;
use MyVendor\Cms\Query\ArticleQueryInterface;
use MyVendor\Cms\Query\AuthorQueryInterface;
use MyVendor\Cms\Query\CategoryQueryInterface;
use MyVendor\Cms\Query\TagQueryInterface;
use Ray\MediaQuery\PagesInterface;

/**
 * Hand-rolled fakes for unit-testing Page/Admin/Article without DI.
 *
 * Used by paths that must run before `$this->resource->post(...)` or any
 * query is touched (e.g. the empty-authors NoRegisteredAuthorException
 * path). All fakes return safe defaults; the resource fake throws because
 * a real ResourceInterface call from this test path would indicate a
 * regression in Article::onPost's pre-flight checks.
 */
final class FakeAdminArticleDeps
{
    public function emptyAuthorQuery(): AuthorQueryInterface
    {
        return new class implements AuthorQueryInterface {
            public function item(int $id): Author|null
            {
                return null;
            }

            public function byEmail(string $email): Author|null
            {
                return null;
            }

            /** @return list<Author> */
            public function list(): array
            {
                return [];
            }
        };
    }

    public function articleQuery(): ArticleQueryInterface
    {
        return new class implements ArticleQueryInterface {
            public function item(int $id): Article|null
            {
                return null;
            }

            public function bySlug(string $slug): Article|null
            {
                return null;
            }

            public function list(
                int|null $categoryId = null,
                int|null $tagId = null,
                int|null $authorId = null,
                string|null $status = null,
                int $perPage = 20,
            ): PagesInterface {
                unset($categoryId, $tagId, $authorId, $status);

                return new FakePages([], $perPage);
            }
        };
    }

    public function categoryQuery(): CategoryQueryInterface
    {
        return new class implements CategoryQueryInterface {
            public function item(int $id): Category|null
            {
                return null;
            }

            public function bySlug(string $slug): Category|null
            {
                return null;
            }

            /** @return list<Category> */
            public function list(): array
            {
                return [];
            }
        };
    }

    public function tagQuery(): TagQueryInterface
    {
        return new class implements TagQueryInterface {
            public function item(int $id): Tag|null
            {
                return null;
            }

            public function bySlug(string $slug): Tag|null
            {
                return null;
            }

            /** @return list<Tag> */
            public function list(): array
            {
                return [];
            }

            /** @return list<Tag> */
            public function listByArticle(int $articleId): array
            {
                return [];
            }
        };
    }

    public function resource(): ResourceInterface
    {
        return new class implements ResourceInterface {
            public function newInstance(mixed $uri): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            public function object(ResourceObject $ro): RequestInterface
            {
                throw new BadMethodCallException(self::class);
            }

            public function uri(mixed $uri): RequestInterface
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function newRequest(Method $method, string $uri, array $query = []): RequestInterface
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function crawl(string $uri, string $linkKey, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function href(string $rel, array $query = [], ResourceObject|null $ro = null): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function get(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function post(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function put(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function patch(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function delete(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function head(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }

            /** @param array<string, mixed> $query */
            public function options(string $uri, array $query = []): ResourceObject
            {
                throw new BadMethodCallException(self::class);
            }
        };
    }
}
