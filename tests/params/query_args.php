<?php

declare(strict_types=1);

/**
 * Positional arguments for every method in the src/Query interface tree.
 *
 * Keys use '<InterfaceShortName>::<method>' so the MediaQuery smoke test can
 * reflect over the Query directory and enforce 1:1 fixture coverage. Values are
 * positional argument lists passed through the test-hal-api-app context, where
 * #[DbQuery] methods are routed to FakeSqlQuery.
 */

return [
    // articles ---------------------------------------------------------
    'ArticleCommandInterface::add' => [
        'smoke-media-query-article',
        'Smoke',
        'Body',
        'Excerpt',
        'draft',
        null,
        1,
        1,
    ],
    'ArticleCommandInterface::delete' => [999],
    'ArticleCommandInterface::update' => [
        999,
        'Updated',
        'Body',
        'Excerpt',
        'published',
        '2026-01-01 00:00:00',
    ],
    'ArticleAffectedRowsCommandInterface::delete' => [999],
    'ArticleAffectedRowsCommandInterface::update' => [
        999,
        'Updated',
        'Body',
        'Excerpt',
        'published',
        '2026-01-01 00:00:00',
    ],
    'ArticleQueryInterface::bySlug' => ['getting-started-with-bear-sunday'],
    'ArticleQueryInterface::item' => [1],
    'ArticleQueryInterface::list' => [null, null, null, 'published', 10],
    'ArticleSelectionQueryInterface::list' => ['published'],
    'ArticleAsArrayQueryInterface::item' => [1],
    'ArticleTagCommandInterface::clear' => [999],
    'ArticleTagCommandInterface::link' => [999, 50],

    // authors ----------------------------------------------------------
    'AuthorCommandInterface::add' => ['Smoke', 'smoke-media-query@example.com', ''],
    'AuthorCommandInterface::update' => [999, 'Updated', 'updated@example.com', ''],
    'AuthorQueryInterface::byEmail' => ['evelyn.moore1@example.com'],
    'AuthorQueryInterface::item' => [1],
    'AuthorQueryInterface::list' => [],

    // categories -------------------------------------------------------
    'CategoryCommandInterface::add' => ['smoke-media-query', 'Smoke', null, null],
    'CategoryCommandInterface::delete' => [999],
    'CategoryCommandInterface::update' => [999, 'Updated', null, null],
    'CategoryQueryInterface::bySlug' => ['technology'],
    'CategoryQueryInterface::item' => [1],
    'CategoryQueryInterface::list' => [],

    // media ------------------------------------------------------------
    'MediaCommandInterface::add' => [
        'smoke-media-query.png',
        'image/png',
        'https://example.com/smoke-media-query.png',
        'smoke',
        100,
        100,
    ],
    'MediaCommandInterface::delete' => [999],
    'MediaQueryInterface::byFilename' => ['media-001.jpg'],
    'MediaQueryInterface::item' => [1],

    // tags -------------------------------------------------------------
    'TagCommandInterface::add' => ['smoke-media-query', 'Smoke'],
    'TagCommandInterface::delete' => [999],
    'TagCommandInterface::update' => [1, 'smoke-media-query', 'Smoke'],
    'TagQueryInterface::bySlug' => ['bear-sunday'],
    'TagQueryInterface::item' => [1],
    'TagQueryInterface::list' => [],
    'TagQueryInterface::listByArticle' => [1],
];
