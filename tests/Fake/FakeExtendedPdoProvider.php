<?php

declare(strict_types=1);

namespace MyVendor\Cms\Fake;

use Aura\Sql\ExtendedPdo;
use Aura\Sql\ExtendedPdoInterface;
use Ray\Di\ProviderInterface;

use function dirname;
use function file_get_contents;
use function json_decode;

use const JSON_THROW_ON_ERROR;

/** @implements ProviderInterface<ExtendedPdoInterface> */
final class FakeExtendedPdoProvider implements ProviderInterface
{
    public function get(): ExtendedPdoInterface
    {
        $pdo = new ExtendedPdo('sqlite::memory:');
        $pdo->exec(<<<'SQL'
CREATE TABLE articles (
    id INTEGER PRIMARY KEY,
    slug TEXT NOT NULL,
    title TEXT NOT NULL,
    body TEXT NOT NULL,
    excerpt TEXT,
    status TEXT NOT NULL,
    published_at TEXT,
    author_id INTEGER NOT NULL,
    category_id INTEGER NOT NULL
)
SQL);

        /** @var list<array<string, mixed>> $rows */
        $rows = json_decode(
            (string) file_get_contents(dirname(__DIR__, 2) . '/var/fake/article.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        foreach ($rows as $row) {
            $pdo->perform(
                <<<'SQL'
INSERT INTO articles (
    id,
    slug,
    title,
    body,
    excerpt,
    status,
    published_at,
    author_id,
    category_id
) VALUES (
    :id,
    :slug,
    :title,
    :body,
    :excerpt,
    :status,
    :publishedAt,
    :authorId,
    :categoryId
)
SQL,
                [
                    'id' => $row['id'],
                    'slug' => $row['slug'],
                    'title' => $row['title'],
                    'body' => $row['body'],
                    'excerpt' => $row['excerpt'] ?? null,
                    'status' => $row['status'],
                    'publishedAt' => $row['publishedAt'] ?? null,
                    'authorId' => $row['authorId'],
                    'categoryId' => $row['categoryId'],
                ],
            );
        }

        return $pdo;
    }
}
