<?php

declare(strict_types=1);

/**
 * GET arguments for every smoke-tested App resource.
 *
 * Keys are canonical `app://self/...` URIs discovered by
 * `ResourceSmokeTest`. Values store the argument maps under the `get` key
 * passed to `ResourceInterface::get()` in the fake app context. The rows use
 * stable fake fixture IDs so the smoke stays hermetic and database-free.
 *
 * @return array<string, array{get: array<string, mixed>}>
 */
return [
    'app://self/article' => ['get' => ['id' => 1]],
    'app://self/articles' => ['get' => []],
    'app://self/auth' => ['get' => []],
    'app://self/author' => ['get' => ['id' => 1]],
    'app://self/authors' => ['get' => []],
    'app://self/cache/articlepreview' => ['get' => ['id' => 1]],
    'app://self/cache/articletags' => ['get' => ['articleId' => 3]],
    'app://self/cache/author' => ['get' => ['id' => 1]],
    'app://self/cache/authorprofile' => ['get' => ['authorId' => 1]],
    'app://self/cache/tag' => ['get' => ['id' => 14]],
    'app://self/categories' => ['get' => []],
    'app://self/category' => ['get' => ['id' => 1]],
    'app://self/crawl/articles' => ['get' => ['authorId' => 1]],
    'app://self/crawl/author' => ['get' => ['id' => 1]],
    'app://self/crawl/tags' => ['get' => ['articleId' => 1]],
    'app://self/media' => ['get' => ['id' => 1]],
    'app://self/tag' => ['get' => ['id' => 1]],
    'app://self/tags' => ['get' => []],
];
