<?php

declare(strict_types=1);

/**
 * Semantic-ex Phase 1 — generate 50 realistic fake records per atomic entity
 * with referential integrity. Output to var/fake/*.json.
 *
 * Deterministic: mt_srand(42).
 *
 * PHP port of bin/semantic-ex/gen-fake.py.
 */

mt_srand(42);

$out = dirname(__DIR__, 2) . '/var/fake';
@mkdir($out, 0755, true);

$pick = static fn (array $arr) => $arr[mt_rand(0, count($arr) - 1)];

// -------- Authors --------
$firstEn = ['Emma','Liam','Olivia','Noah','Ava','Mia','Lucas','Sophia','Ethan','Isabella','Mason','Amelia','Leo','Charlotte','Jack','Harper','Oliver','Evelyn','Jacob','Ella','James','Scarlett','Henry','Grace','Daniel'];
$lastEn  = ['Smith','Johnson','Williams','Brown','Jones','Garcia','Miller','Davis','Rodriguez','Martinez','Hernandez','Lopez','Gonzalez','Wilson','Anderson','Thomas','Taylor','Moore','Jackson','Martin','Lee','Perez','Thompson','White','Harris'];
$firstJa = ['田中','佐藤','鈴木','高橋','伊藤','渡辺','山本','中村','小林','加藤','吉田','山田','佐々木','山口','松本','井上','木村','林','清水','斎藤'];
$givenJa = ['太郎','花子','健一','美咲','直樹','あかり','翔太','陽子','大輔','さくら','修平','由美','雄太','香織','拓海'];
$bios = [
    'Full-stack engineer blogging about PHP internals.',
    'Editor-in-chief. Loves long-form storytelling.',
    'Front-end developer, typography nerd.',
    'Travel writer covering Southeast Asia.',
    'Freelance photographer and essayist.',
    'テックブログ執筆者。BEAR.Sundayが好き。',
    '料理と発酵食品について書いています。',
    'Writer. Cat person. Occasional poet.',
    'SRE turned technical writer.',
    'Data journalist covering climate topics.',
    '新米ライター。コーヒーと本をこよなく愛する。',
    'DevRel at a small startup.',
    '',
    'I write about sustainable gardening.',
    'UX designer; sometimes I write too.',
];

$authors = [];
$usedEmails = [];
for ($i = 1; $i <= 50; $i++) {
    if ($i % 3 === 0) {
        $name = $pick($firstJa) . ' ' . $pick($givenJa);
    } else {
        $name = $pick($firstEn) . ' ' . $pick($lastEn);
    }

    // ASCII-safe email base
    $base = strtolower($name);
    $base = preg_replace('/[^a-z0-9 ]/', '', $base) ?? '';
    $base = trim(str_replace(' ', '.', $base), '.');
    if ($base === '') {
        $base = 'author' . $i;
    }

    $email = "{$base}{$i}@example.com";
    while (in_array($email, $usedEmails, true)) {
        $email = sprintf('%s%d.%d@example.com', $base, $i, mt_rand(10, 99));
    }

    $usedEmails[] = $email;
    $authors[] = [
        'id' => $i,
        'name' => $name,
        'email' => $email,
        'bio' => $pick($bios),
    ];
}

// -------- Categories --------
$catDefs = [
    ['technology','Technology'], ['programming','Programming'], ['php','PHP'], ['javascript','JavaScript'],
    ['architecture','Architecture'], ['design','Design'], ['travel','Travel'], ['food','Food'],
    ['photography','Photography'], ['books','Books'], ['lifestyle','Lifestyle'], ['culture','Culture'],
    ['health','Health'], ['science','Science'], ['business','Business'], ['news','News'],
    ['opinion','Opinion'], ['interviews','Interviews'], ['tutorials','Tutorials'], ['case-studies','Case Studies'],
    ['release-notes','Release Notes'], ['deep-dives','Deep Dives'], ['short-reads','Short Reads'],
    ['beginner','Beginner'], ['advanced','Advanced'], ['web','Web'], ['mobile','Mobile'],
    ['devops','DevOps'], ['cloud','Cloud'], ['databases','Databases'], ['testing','Testing'],
    ['performance','Performance'], ['security','Security'], ['accessibility','Accessibility'],
    ['ai','AI'], ['machine-learning','Machine Learning'], ['open-source','Open Source'],
    ['community','Community'], ['career','Career'], ['events','Events'], ['book-reviews','Book Reviews'],
    ['commentary','Commentary'], ['editorial','Editorial'], ['guides','Guides'], ['references','References'],
    ['tools','Tools'], ['frameworks','Frameworks'], ['libraries','Libraries'], ['standards','Standards'],
    ['history','History of Computing'],
];
$catDescriptions = [
    null,
    'Posts about coding practice, patterns and language internals.',
    'Essays, tips and reference material.',
    'Field reports and case studies.',
    'Short daily notes.',
    'Long-form technical deep dives.',
    '日本語での記事をまとめます。',
    '',
];
$categories = [];
foreach ($catDefs as $i => [$slug, $name]) {
    $i++;
    $parent = null;
    if ($i > 10 && $i % 4 === 0) {
        $parent = mt_rand(1, 10);
    }

    $categories[] = [
        'id' => $i,
        'slug' => $slug,
        'name' => $name,
        'description' => $pick($catDescriptions),
        'parentId' => $parent,
    ];
}

// -------- Tags --------
$tagDefs = [
    ['bear-sunday','BEAR.Sunday'], ['ray-di','Ray.Di'], ['ray-aop','Ray.Aop'], ['media-query','MediaQuery'],
    ['alps','ALPS'], ['hal','HAL'], ['rest','REST'], ['tutorial','Tutorial'], ['intro','Introduction'],
    ['advanced','Advanced'], ['php','PHP'], ['php8','PHP 8'], ['attributes','Attributes'],
    ['di','Dependency Injection'], ['cqrs','CQRS'], ['bdr','BDR Pattern'], ['testing','Testing'],
    ['phpunit','PHPUnit'], ['doctrine','Doctrine'], ['migrations','Migrations'], ['mysql','MySQL'],
    ['sqlite','SQLite'], ['javascript','JavaScript'], ['typescript','TypeScript'], ['vue','Vue'],
    ['react','React'], ['nginx','Nginx'], ['docker','Docker'], ['malt','Malt'],
    ['opinion','Opinion'], ['news','News'], ['release','Release'], ['performance','Performance'],
    ['security','Security'], ['best-practice','Best Practice'], ['design-pattern','Design Pattern'],
    ['beginner','Beginner'], ['pro-tip','Pro Tip'], ['photo-essay','Photo Essay'],
    ['travel-log','Travel Log'], ['review','Review'], ['interview','Interview'], ['case-study','Case Study'],
    ['workflow','Workflow'], ['tooling','Tooling'], ['accessibility','Accessibility'], ['community','Community'],
    ['event','Event'], ['career','Career'], ['japanese','日本語'],
];
$tags = [];
foreach ($tagDefs as $i => [$slug, $name]) {
    $tags[] = ['id' => $i + 1, 'slug' => $slug, 'name' => $name];
}

// -------- Articles --------
$titles = [
    'Getting Started with BEAR.Sunday', 'Why Ray.MediaQuery Changes How We Write SQL',
    'A Deep Dive into ALPS Profiles', 'HAL+JSON in Practice', 'BDR Pattern Explained',
    'Migrating from Phinx to Doctrine Migrations', 'Testing BEAR Resources without a Database',
    'The Hidden Power of PHP Attributes', 'Composing Modules with Ray.Di', 'From CRUD to Semantic REST',
    'BEAR.Cms アーキテクチャ概観', 'ALPSで設計を共有する', 'Fakeデータから制約を発見する',
    'Ray.MediaQueryのBDR実装例', '小さなCMSの育て方', 'Semantic-Ex Method: An Introduction',
    'Building a Reference CMS for BEAR.Sunday', 'Content Modeling: Articles, Categories, Tags',
    'Why We Dropped JS from Our Admin', 'HAL Links and Embedded Resources',
    'Domain-Driven Design on a Thin Slice', 'Observing Data to Derive Constraints',
    'Pagination without Pain', 'The Case Against Premature Abstraction',
    'Notes on Long-Form Tech Writing', 'Working with Readonly Classes in PHP 8.2',
    'Handling Transactions in a Resource-Oriented App', 'Two Weeks with Malt',
    'Setting Up Doctrine Migrations in BEAR', 'Using Doctrine DBAL for Schema Diffs',
    'Type Hints over Comments', 'When Your Fake is Better than Your Prod',
    'Weekend Notes: Sketching an API', 'Cache Invalidation for Writers',
    'A Tour of BEAR.QueryRepository', 'Refactoring Toward a Repository Boundary',
    'The Joy of a Tiny Codebase', "Writing Migrations You Won't Regret",
    'Nginx, PHP-FPM and Malt: A Short Guide', 'Designing URIs with ALPS in Mind',
    'What HAL Taught Me about APIs', 'Interviewing Without a Whiteboard',
    'Photo Essay: Kyoto in Spring', "A Traveler's Reading List", 'Recipes from a Tiny Kitchen',
    'The Unreasonable Effectiveness of README.md', 'The Plural of Anecdote is Data',
    'One Script, Many Screens', 'A Day with a New Framework', 'Notes Toward a Better Onboarding',
];

$bodiesShort = ['A brief note.', 'Quick thoughts on the topic.', 'TL;DR included at the top.'];
$bodiesLongEn = [
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
    "In this article we walk through a concrete example: how we modeled a minimal CMS using BEAR.Sunday, and how the BDR pattern shaped the read side. We also discuss the write side and how it fits in.\n\nWe start by reviewing the ALPS profile, then we derive JSON Schema from observed data, and finally we generate stubs for the Query interfaces.",
    "When you treat your data as the source of truth about constraints (rather than choosing arbitrary limits up front), surprising things happen. Some fields that you assumed were 'short strings' turn out to commonly reach 200+ characters. Others you thought would be large are almost always under 50 characters.\n\nThis is the semantic-ex method applied to practice.",
];
$bodiesLongJa = [
    "この記事ではBEAR.Sundayを用いた参照実装CMSを紹介します。BDRパターンにより読み込み側と書き込み側を分離し、保守しやすいコードベースを目指します。\n\n読み込み側はRay.MediaQueryでSQLを外出しし、書き込み側はRay.InputQueryで入力をオブジェクト化します。",
    'ALPSプロファイルをまず書き、Fakeデータを生成してから制約を発見するというアプローチは、現実のデータに裏打ちされたスキーマを生むという意味で大きな利点があります。',
    '短いメモ。',
];

$articles = [];
$slugsUsed = [];
for ($i = 1; $i <= 50; $i++) {
    $title = $titles[($i - 1) % count($titles)];
    if ($i > count($titles)) {
        $title .= sprintf(' (part %d)', 1 + intdiv($i - 1, count($titles)));
    }

    // slug
    $base = strtolower($title);
    $base = preg_replace('/[^a-z0-9]+/', '-', $base) ?? '';
    $base = trim($base, '-');
    if ($base === '') {
        $base = 'article-' . $i;
    }

    $slug = substr($base, 0, 60);
    $j = 2;
    while (in_array($slug, $slugsUsed, true)) {
        $slug = sprintf('%s-%d', substr($base, 0, 55), $j++);
    }

    $slugsUsed[] = $slug;

    if ($i % 7 === 0) {
        $body = $pick($bodiesShort);
    } elseif ($i % 3 === 0) {
        $body = $pick($bodiesLongJa);
    } else {
        $body = $pick($bodiesLongEn);
    }

    $excerpt = mb_substr(strtok($body, "\n"), 0, 140);

    $status = ($i % 6 === 0) ? 'draft' : 'published';
    $publishedAt = $status === 'draft'
        ? null
        : sprintf('2026-%02d-%02dT%02d:%02d:00Z', (($i - 1) % 12) + 1, (($i - 1) % 28) + 1, 9 + ($i % 8), ($i * 7) % 60);

    $articles[] = [
        'id' => $i,
        'slug' => $slug,
        'title' => $title,
        'body' => $body,
        'excerpt' => $excerpt,
        'status' => $status,
        'publishedAt' => $publishedAt,
        'authorId' => (($i - 1) % count($authors)) + 1,
        'categoryId' => (($i - 1) % count($categories)) + 1,
    ];
}

// -------- ArticleTag (1..4 random tags per article) --------
$articleTags = [];
foreach ($articles as $a) {
    $k = mt_rand(1, 4);
    $allTagIds = range(1, count($tags));
    shuffle($allTagIds);
    foreach (array_slice($allTagIds, 0, $k) as $t) {
        $articleTags[] = ['articleId' => $a['id'], 'tagId' => $t];
    }
}

$articleListArticles = $articles;
// Supplemental Page/Qiq escaping fixture; not part of the 50-record semantic corpus.
$articles[] = [
    'id' => 51,
    'slug' => 'xss-regression',
    'title' => '<script>alert("xss")</script>Bad',
    'body' => 'Plain text body for XSS regression test.',
    'excerpt' => 'Excerpt with <em>html</em> and & ampersand.',
    'status' => 'draft',
    'publishedAt' => '2020-01-01T00:00:00Z',
    'authorId' => 1,
    'categoryId' => 1,
];

// -------- Media --------
$mimeTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];
$altSamples = [
    'A minimalist workspace with a laptop and coffee cup.',
    'Mountain peaks in winter light.',
    'Screenshot of a BEAR.Sunday console output.',
    'Portrait of a developer smiling at their screen.',
    'Diagram illustrating the BDR pattern.',
    '', null,
    '桜の並木道', 'キーボードの俯瞰写真', 'ホワイトボード上のスケッチ',
    'A stack of technical books.', 'Kitchen counter with a cutting board and knife.',
    'Architectural detail of a temple roof.', 'City skyline at dusk.',
    'A cat curled up on a keyboard.',
];
$widths = [320, 640, 800, 1024, 1280, 1600, 1920];
$heights = [240, 480, 600, 768, 960, 1080, 1200];
$media = [];
for ($i = 1; $i <= 50; $i++) {
    $mime = $mimeTypes[($i - 1) % count($mimeTypes)];
    $ext = $mime === 'image/jpeg' ? 'jpg' : explode('+', explode('/', $mime)[1])[0];
    $filename = sprintf('media-%03d.%s', $i, $ext);
    if ($mime === 'image/svg+xml') {
        $w = 0;
        $h = 0;
    } else {
        $w = $pick($widths);
        $h = $pick($heights);
    }

    $media[] = [
        'id' => $i,
        'filename' => $filename,
        'mimeType' => $mime,
        'url' => '/media/' . $filename,
        'alt' => $altSamples[($i - 1) % count($altSamples)],
        'width' => $w,
        'height' => $h,
    ];
}

// -------- write --------
$write = static function (string $name, mixed $data) use ($out): void {
    file_put_contents($out . '/' . $name, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
};

$write('author.json', $authors);
$write('category.json', $categories);
$write('tag.json', $tags);
$write('article.json', $articles);
$write('articleList.json', [
    'items' => array_map(static fn ($a) => array_diff_key($a, ['body' => true]), array_slice($articleListArticles, 0, 20)),
    'page' => 1,
    'perPage' => 20,
    'totalCount' => count($articleListArticles),
]);
$write('articleTag.json', $articleTags);
$write('categoryList.json', [
    'items' => array_slice($categories, 0, 30),
    'page' => 1, 'perPage' => 30, 'totalCount' => count($categories),
]);
$write('tagList.json', [
    'items' => $tags, 'page' => 1, 'perPage' => 50, 'totalCount' => count($tags),
]);
$write('media.json', $media);

printf(
    "authors=%d categories=%d tags=%d articles=%d articleTags=%d media=%d\n",
    count($authors), count($categories), count($tags), count($articleListArticles), count($articleTags), count($media),
);
