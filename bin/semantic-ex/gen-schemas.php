<?php

declare(strict_types=1);

/**
 * Semantic-ex Phase 2 + 3 — observe fake data and emit JSON Schemas
 * with constraints derived from the data, not chosen up-front.
 *
 * maxLength = ceil(observed_max * 1.5) snapped to a 'nice' boundary;
 * minLength = observed minimum.
 *
 * PHP port of bin/semantic-ex/gen-schemas.py.
 */

$fake = dirname(__DIR__, 2) . '/var/fake';
$out = dirname(__DIR__, 2) . '/var/json_schema';
@mkdir($out, 0755, true);

$baseId = 'https://example.com/bear-cms';

function ceilToNice(int $n): int
{
    foreach ([50, 100, 200, 500, 1000, 2000, 5000, 10000] as $boundary) {
        if ($n <= $boundary) {
            return $boundary;
        }
    }

    return (int) (ceil($n * 1.5 / 1000) * 1000);
}

/** @return array{nulls:int, min_len:int, max_len:int, typical_len:int} */
function observeStr(array $values): array
{
    $nonnull = array_filter($values, static fn ($v) => $v !== null);
    $lengths = array_map(static fn ($v) => mb_strlen((string) $v), $nonnull);
    sort($lengths);

    return [
        'nulls' => count($values) - count($nonnull),
        'min_len' => $lengths === [] ? 0 : (int) min($lengths),
        'max_len' => $lengths === [] ? 0 : (int) max($lengths),
        'typical_len' => $lengths === [] ? 0 : $lengths[intdiv(count($lengths), 2)],
    ];
}

/** @return array{nulls:int, min:int, max:int} */
function observeInt(array $values): array
{
    $nonnull = array_filter($values, static fn ($v) => $v !== null);

    return [
        'nulls' => count($values) - count($nonnull),
        'min' => $nonnull === [] ? 0 : (int) min($nonnull),
        'max' => $nonnull === [] ? 0 : (int) max($nonnull),
    ];
}

function fields(array $records, string $key): array
{
    return array_map(static fn ($r) => $r[$key] ?? null, $records);
}

/** @return array<string, mixed> */
function strFieldSchema(array $records, string $key, string $description, ?string $format = null, ?string $pattern = null, bool $nullable = false): array
{
    $obs = observeStr(fields($records, $key));
    $minLen = ($obs['max_len'] === 0 && $obs['nulls'] > 0) ? 0 : $obs['min_len'];
    $maxLen = ($obs['max_len'] === 0 && $obs['nulls'] > 0) ? 100 : ceilToNice($obs['max_len']);
    $schema = [
        'type' => $nullable ? ['string', 'null'] : 'string',
        'description' => $description,
        'minLength' => $minLen,
        'maxLength' => $maxLen,
    ];
    if ($format !== null) {
        $schema['format'] = $format;
    }

    if ($pattern !== null) {
        $schema['pattern'] = $pattern;
    }

    return $schema;
}

/** @return array<string, mixed> */
function intFieldSchema(array $records, string $key, string $description, bool $nullable = false, ?int $minimum = null): array
{
    $obs = observeInt(fields($records, $key));
    $schema = [
        'type' => $nullable ? ['integer', 'null'] : 'integer',
        'description' => $description,
        'minimum' => $minimum ?? $obs['min'],
    ];

    return $schema;
}

$articles = json_decode(file_get_contents($fake . '/article.json'), true);
$articles = array_values(array_filter(
    $articles,
    static fn (array $article): bool => ($article['slug'] ?? null) !== 'xss-regression',
));
$categories = json_decode(file_get_contents($fake . '/category.json'), true);
$tags = json_decode(file_get_contents($fake . '/tag.json'), true);
$authors = json_decode(file_get_contents($fake . '/author.json'), true);
$media = json_decode(file_get_contents($fake . '/media.json'), true);

$articleSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/article',
    'title' => 'Article',
    'description' => 'ALPS Article state. Single article with embedded Author, Category and Tags.',
    'type' => 'object',
    'required' => ['id', 'slug', 'title', 'body', 'status', 'authorId', 'categoryId'],
    'properties' => [
        'id' => intFieldSchema($articles, 'id', 'Primary key', false, 1),
        'slug' => strFieldSchema($articles, 'slug', 'URL-safe unique slug', null, '^[a-z0-9][a-z0-9-]*$'),
        'title' => strFieldSchema($articles, 'title', 'Article headline'),
        'body' => strFieldSchema($articles, 'body', 'Full article body'),
        'excerpt' => strFieldSchema($articles, 'excerpt', 'Short summary used in list views', null, null, true),
        'status' => ['type' => 'string', 'description' => 'Lifecycle status', 'enum' => ['draft', 'published']],
        'publishedAt' => ['type' => ['string', 'null'], 'description' => 'Publication timestamp; null for drafts', 'format' => 'date-time'],
        'authorId' => intFieldSchema($articles, 'authorId', 'Foreign key to Author', false, 1),
        'categoryId' => intFieldSchema($articles, 'categoryId', 'Foreign key to Category', false, 1),
    ],
];

$articleListSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/articleList',
    'title' => 'ArticleList',
    'description' => 'Paginated list of article summaries',
    'type' => 'object',
    'required' => ['items', 'page', 'perPage', 'count', 'totalCount'],
    'properties' => [
        'items' => [
            'type' => 'array',
            'items' => [
                'type' => 'object',
                'required' => ['id', 'slug', 'title', 'status'],
                'properties' => [
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'slug' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'excerpt' => ['type' => ['string', 'null']],
                    'status' => ['type' => 'string', 'enum' => ['draft', 'published']],
                    'publishedAt' => ['type' => ['string', 'null'], 'format' => 'date-time'],
                    'authorId' => ['type' => 'integer', 'minimum' => 1],
                    'categoryId' => ['type' => 'integer', 'minimum' => 1],
                ],
            ],
        ],
        'page' => ['type' => 'integer', 'minimum' => 1],
        'perPage' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
        'count' => ['type' => 'integer', 'minimum' => 0],
        'totalCount' => ['type' => 'integer', 'minimum' => 0],
    ],
];

$categorySchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/category',
    'title' => 'Category',
    'type' => 'object',
    'required' => ['id', 'slug', 'name'],
    'properties' => [
        'id' => intFieldSchema($categories, 'id', 'Primary key', false, 1),
        'slug' => strFieldSchema($categories, 'slug', 'URL-safe unique slug', null, '^[a-z0-9][a-z0-9-]*$'),
        'name' => strFieldSchema($categories, 'name', 'Display name'),
        'description' => strFieldSchema($categories, 'description', 'Optional description', null, null, true),
        'parentId' => intFieldSchema($categories, 'parentId', 'Parent category id; null for top-level', true, 1),
    ],
];

$categoryListSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/categoryList',
    'title' => 'CategoryList',
    'type' => 'object',
    'required' => ['items'],
    'properties' => [
        'items' => ['type' => 'array', 'items' => ['$ref' => 'category.json']],
        'totalCount' => ['type' => 'integer', 'minimum' => 0],
    ],
];

$tagSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/tag',
    'title' => 'Tag',
    'type' => 'object',
    'required' => ['id', 'slug', 'name'],
    'properties' => [
        'id' => intFieldSchema($tags, 'id', 'Primary key', false, 1),
        'slug' => strFieldSchema($tags, 'slug', 'URL-safe unique slug', null, '^[a-z0-9][a-z0-9-]*$'),
        'name' => strFieldSchema($tags, 'name', 'Display name'),
    ],
];

$tagListSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/tagList',
    'title' => 'TagList',
    'type' => 'object',
    'required' => ['items'],
    'properties' => [
        'items' => ['type' => 'array', 'items' => ['$ref' => 'tag.json']],
        'totalCount' => ['type' => 'integer', 'minimum' => 0],
    ],
];

$authorSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/author',
    'title' => 'Author',
    'type' => 'object',
    'required' => ['id', 'name', 'email'],
    'properties' => [
        'id' => intFieldSchema($authors, 'id', 'Primary key', false, 1),
        'name' => strFieldSchema($authors, 'name', 'Full name'),
        'email' => strFieldSchema($authors, 'email', 'Email address', 'email'),
        'bio' => strFieldSchema($authors, 'bio', 'Optional biography', null, null, true),
    ],
];

$authorListSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/authorList',
    'title' => 'AuthorList',
    'type' => 'object',
    'required' => ['items'],
    'properties' => [
        'items' => ['type' => 'array', 'items' => ['$ref' => 'author.json']],
        'totalCount' => ['type' => 'integer', 'minimum' => 0],
    ],
];

$mediaSchema = [
    '$schema' => 'https://json-schema.org/draft/2020-12/schema',
    '$id' => $baseId . '/media',
    'title' => 'Media',
    'type' => 'object',
    'required' => ['id', 'filename', 'mimeType', 'url'],
    'properties' => [
        'id' => intFieldSchema($media, 'id', 'Primary key', false, 1),
        'filename' => strFieldSchema($media, 'filename', 'Original filename'),
        'mimeType' => strFieldSchema($media, 'mimeType', 'MIME type'),
        'url' => strFieldSchema($media, 'url', 'Public URL path', 'uri-reference'),
        'alt' => strFieldSchema($media, 'alt', 'Accessibility alt text', null, null, true),
        'width' => intFieldSchema($media, 'width', 'Pixel width; 0 for SVG', false, 0),
        'height' => intFieldSchema($media, 'height', 'Pixel height; 0 for SVG', false, 0),
    ],
];

$write = static function (string $name, array $schema) use ($out): void {
    file_put_contents($out . '/' . $name, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
};

$write('article.json', $articleSchema);
$write('articleList.json', $articleListSchema);
$write('category.json', $categorySchema);
$write('categoryList.json', $categoryListSchema);
$write('tag.json', $tagSchema);
$write('tagList.json', $tagListSchema);
$write('author.json', $authorSchema);
$write('authorList.json', $authorListSchema);
$write('media.json', $mediaSchema);

// observations.md (lightweight)
$obs = "# Fake data observations (semantic-ex Phase 2)\n\n";
$obs .= "Generated from var/fake/*.json — 50 records per atomic entity.\n\n";
$logFields = [
    'Article' => [$articles, ['id','slug','title','body','excerpt','status','publishedAt','authorId','categoryId']],
    'Category' => [$categories, ['id','slug','name','description','parentId']],
    'Tag' => [$tags, ['id','slug','name']],
    'Author' => [$authors, ['id','name','email','bio']],
    'Media' => [$media, ['id','filename','mimeType','url','alt','width','height']],
];
foreach ($logFields as $label => [$rows, $names]) {
    $obs .= "## $label\n";
    foreach ($names as $f) {
        $vals = fields($rows, $f);
        $isInt = true;
        foreach ($vals as $v) {
            if ($v !== null && ! is_int($v)) {
                $isInt = false;
                break;
            }
        }

        if ($isInt) {
            $o = observeInt($vals);
            $obs .= sprintf("- `%s` (int): min=%d max=%d nulls=%d/%d\n", $f, $o['min'], $o['max'], $o['nulls'], count($vals));
        } else {
            $o = observeStr($vals);
            $obs .= sprintf("- `%s` (str): min_len=%d max_len=%d typical=%d nulls=%d/%d\n", $f, $o['min_len'], $o['max_len'], $o['typical_len'], $o['nulls'], count($vals));
        }
    }

    $obs .= "\n";
}

file_put_contents($fake . '/observations.md', $obs);

echo "Wrote schemas to {$out}\n";
foreach (scandir($out) as $f) {
    if ($f === '.' || $f === '..' || ! str_ends_with($f, '.json')) {
        continue;
    }

    echo "  $f\n";
}
