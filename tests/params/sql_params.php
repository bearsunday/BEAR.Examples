<?php

declare(strict_types=1);

/**
 * Bind values for every var/db/sql/*.sql file.
 *
 * Format is intentionally compatible with Koriym.SqlQuality's
 * `tests/params/sql_params.php` convention: a flat map of
 * `'<filename>.sql' => [bindName => value, ...]`. The same file
 * can therefore feed our PHPUnit smoke (SqlSmokeTest) and
 * `vendor/bin/sql-quality analyze --params=tests/params/sql_params.php`
 * if the latter is added to the dev toolchain.
 *
 * Values reference rows that bin/seed.php loads from var/fake/*.json,
 * so SELECTs return real rows and FK-constrained INSERTs succeed.
 * Each smoke run wraps the execute in a transaction and rolls back,
 * so write SQLs do not have to use unique keys.
 */

return [
    // articles ---------------------------------------------------------
    'article_add.sql' => [
        'slug' => 'smoke-test-article',
        'title' => 'Smoke',
        'body' => 'Body',
        'excerpt' => 'Excerpt',
        'status' => 'draft',
        'publishedAt' => null,
        'authorId' => 1,
        'categoryId' => 1,
    ],
    'article_as_array_item.sql' => ['id' => 1],
    'article_by_slug.sql' => ['slug' => 'getting-started-with-bear-sunday'],
    'article_delete.sql' => ['id' => 1],
    'article_item.sql' => ['id' => 1],
    'article_list.sql' => [
        'categoryId' => null,
        'authorId' => null,
        'status' => 'published',
        'tagId' => null,
    ],
    'article_selection_list.sql' => ['status' => 'published'],
    'article_sqlquery_item.sql' => ['id' => 1],
    'article_sqlquery_next.sql' => ['publishedAt' => '2026-01-01 00:00:00', 'id' => 1],
    'article_sqlquery_previous.sql' => ['publishedAt' => '2030-01-01 00:00:00', 'id' => 999],
    'article_tag_clear.sql' => ['articleId' => 1],
    'article_tag_link.sql' => ['articleId' => 1, 'tagId' => 50],
    'article_update.sql' => [
        'title' => 'Updated',
        'body' => 'Body',
        'excerpt' => 'Excerpt',
        'status' => 'published',
        'publishedAt' => '2026-01-01 00:00:00',
        'id' => 1,
    ],

    // authors ----------------------------------------------------------
    'author_add.sql' => ['name' => 'Smoke', 'email' => 'smoke@example.com', 'bio' => ''],
    'author_by_email.sql' => ['email' => 'evelyn.moore1@example.com'],
    'author_item.sql' => ['id' => 1],
    'author_list.sql' => [],
    'author_update.sql' => ['name' => 'Updated', 'email' => 'updated@example.com', 'bio' => '', 'id' => 1],

    // categories -------------------------------------------------------
    'category_add.sql' => ['slug' => 'smoke', 'name' => 'Smoke', 'description' => null, 'parentId' => null],
    'category_by_slug.sql' => ['slug' => 'technology'],
    'category_delete.sql' => ['id' => 999],
    'category_item.sql' => ['id' => 1],
    'category_list.sql' => [],
    'category_update.sql' => ['name' => 'Updated', 'description' => null, 'parentId' => null, 'id' => 1],

    // media ------------------------------------------------------------
    'media_add.sql' => [
        'filename' => 'smoke.png',
        'mimeType' => 'image/png',
        'url' => 'https://example.com/smoke.png',
        'alt' => 'smoke',
        'width' => 100,
        'height' => 100,
    ],
    'media_by_filename.sql' => ['filename' => 'media-001.jpg'],
    'media_delete.sql' => ['id' => 1],
    'media_item.sql' => ['id' => 1],

    // tags -------------------------------------------------------------
    'tag_add.sql' => ['slug' => 'smoke', 'name' => 'Smoke'],
    'tag_by_slug.sql' => ['slug' => 'bear-sunday'],
    'tag_delete.sql' => ['id' => 1],
    'tag_item.sql' => ['id' => 1],
    'tag_list.sql' => [],
    'tag_list_by_article.sql' => ['articleId' => 1],
    'tag_update.sql' => ['slug' => 'smoke', 'name' => 'Smoke', 'id' => 1],
];
