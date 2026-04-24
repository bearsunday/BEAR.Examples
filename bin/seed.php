<?php

declare(strict_types=1);

/**
 * Seed the database from var/fake/*.json (same data that backs FakeSqlQuery).
 *
 * Usage: php bin/seed.php [--truncate]
 *
 * Tables filled: authors, categories, tags, articles, article_tags, media.
 * Referential integrity matches Phase 3 output, so seeded data is a superset
 * of what FakeSqlQuery returns in test/dev.
 */

use Doctrine\DBAL\Connection;

require dirname(__DIR__) . '/vendor/autoload.php';

/** @var Connection $conn */
$conn = require dirname(__DIR__) . '/migrations-db.php';

$fakeDir = dirname(__DIR__) . '/var/fake';
$loadJson = static fn (string $name) => json_decode(
    (string) file_get_contents($fakeDir . '/' . $name),
    true,
    512,
    JSON_THROW_ON_ERROR,
);

$truncate = in_array('--truncate', $argv, true);
if ($truncate) {
    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
    foreach (['article_tags', 'articles', 'tags', 'categories', 'authors', 'media'] as $t) {
        $conn->executeStatement(sprintf('TRUNCATE TABLE `%s`', $t));
    }

    $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDOUT, "Truncated existing tables.\n");
}

$authors = $loadJson('data-50.author.json');
foreach ($authors as $row) {
    $conn->insert('authors', [
        'id' => $row['id'],
        'name' => $row['name'],
        'email' => $row['email'],
        'bio' => (string) ($row['bio'] ?? ''),
    ]);
}

fwrite(STDOUT, sprintf("Seeded %d authors.\n", count($authors)));

// Categories: insert parentless first to satisfy FK self-reference
$categories = $loadJson('data-50.category.json');
usort($categories, static fn ($a, $b) => ($a['parentId'] ?? 0) <=> ($b['parentId'] ?? 0));
foreach ($categories as $row) {
    $conn->insert('categories', [
        'id' => $row['id'],
        'slug' => $row['slug'],
        'name' => $row['name'],
        'description' => $row['description'] ?: null,
        'parent_id' => $row['parentId'] ?? null,
    ]);
}

fwrite(STDOUT, sprintf("Seeded %d categories.\n", count($categories)));

$tags = $loadJson('data-50.tag.json');
foreach ($tags as $row) {
    $conn->insert('tags', [
        'id' => $row['id'],
        'slug' => $row['slug'],
        'name' => $row['name'],
    ]);
}

fwrite(STDOUT, sprintf("Seeded %d tags.\n", count($tags)));

$articles = $loadJson('data-50.article.json');
foreach ($articles as $row) {
    $publishedAt = null;
    if (! empty($row['publishedAt'])) {
        $publishedAt = (new DateTimeImmutable($row['publishedAt']))->format('Y-m-d H:i:s');
    }

    $conn->insert('articles', [
        'id' => $row['id'],
        'slug' => $row['slug'],
        'title' => $row['title'],
        'body' => $row['body'],
        'excerpt' => $row['excerpt'] ?? null,
        'status' => $row['status'],
        'published_at' => $publishedAt,
        'author_id' => $row['authorId'],
        'category_id' => $row['categoryId'],
    ]);
}

fwrite(STDOUT, sprintf("Seeded %d articles.\n", count($articles)));

$articleTags = $loadJson('data-50.articleTag.json');
foreach ($articleTags as $row) {
    $conn->insert('article_tags', [
        'article_id' => $row['articleId'],
        'tag_id' => $row['tagId'],
    ]);
}

fwrite(STDOUT, sprintf("Seeded %d article_tags links.\n", count($articleTags)));

$media = $loadJson('data-50.media.json');
foreach ($media as $row) {
    $conn->insert('media', [
        'id' => $row['id'],
        'filename' => $row['filename'],
        'mime_type' => $row['mimeType'],
        'url' => $row['url'],
        'alt' => $row['alt'] ?? null,
        'width' => (int) $row['width'],
        'height' => (int) $row['height'],
    ]);
}

fwrite(STDOUT, sprintf("Seeded %d media.\n", count($media)));
fwrite(STDOUT, "Done.\n");
