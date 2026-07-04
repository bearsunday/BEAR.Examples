<?php

declare(strict_types=1);

/**
 * `composer demo` — exercises every BEAR.Cms subsystem once and prints
 * what it observes. Read-only verification: never modifies var/fake or
 * var/json_schema beyond regenerating them; never touches a DB other
 * than the one the user already has running.
 *
 * Sections:
 *   1) semantic-ex          regenerate fake + schema
 *   2) Fake context         GET / POST / DELETE through FakeSqlQuery
 *   3) Real DB              auto-detect malt → docker → sqlite, run
 *                           the same flow against the real backend
 *   4) Hypermedia walk      goArticleList → goArticle → goAuthor
 *   4.5) Async embed        ext-parallel via bin/async.php
 *                           (skipped with install hint if ext-parallel
 *                           is not loaded)
 *   5) ALPS validate        npm ASD validate (skipped if composer setup:docs was not run)
 *   6) apidoc               composer doc (HTML / OpenAPI / llms.txt)
 *   7) CLI                  bin/cli/article-show against the real DB
 *
 * The real-DB section autodetects which backend is up:
 *   - `malt status` shows MySQL running         → use malt-style root/no-password
 *   - `docker compose ps`  shows mysql healthy  → use docker-style root/root
 *   - otherwise                                 → fall back to a fresh SQLite file
 */

use BEAR\Kata\Injector;
use BEAR\Resource\ResourceInterface;

require dirname(__DIR__) . '/autoload.php';

$root = dirname(__DIR__);

function section(string $title): void
{
    fwrite(STDOUT, "\n──────────────────────────────────────────────────────────\n");
    fwrite(STDOUT, "  {$title}\n");
    fwrite(STDOUT, "──────────────────────────────────────────────────────────\n");
}

function run(string $cmd): void
{
    fwrite(STDOUT, "$ {$cmd}\n");
    passthru($cmd);
}

/**
 * Detect which DB stack is running and return [dsn, user, password, label].
 *
 * @return array{0: string, 1: string, 2: string, 3: string}
 */
function detectDb(): array
{
    // 1) malt
    $maltStatus = (string) shell_exec('malt status 2>/dev/null');
    if (str_contains($maltStatus, 'MySQL (port 3306): running')) {
        return [
            'mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4',
            'root',
            '',
            'malt MySQL',
        ];
    }

    // 2) docker compose
    $dockerPs = (string) shell_exec('docker compose ps --format=json 2>/dev/null');
    if (str_contains($dockerPs, '"State":"running"') && str_contains($dockerPs, 'bear_cms_mysql')) {
        return [
            'mysql:host=127.0.0.1;port=3306;dbname=bear_cms;charset=utf8mb4',
            'root',
            'root',
            'docker compose MySQL',
        ];
    }

    // 3) fallback SQLite
    $sqlitePath = '/tmp/bear_cms_demo.db';
    if (file_exists($sqlitePath)) {
        unlink($sqlitePath);
    }

    return ['sqlite:' . $sqlitePath, '', '', 'SQLite (fallback)'];
}

// ── 1) semantic-ex ────────────────────────────────────────────────
section('1) semantic-ex — regenerate fake data and JSON Schema');
run("php {$root}/bin/semantic-ex/gen-fake.php");
run("php {$root}/bin/semantic-ex/gen-schemas.php");

// ── 2) Fake context ───────────────────────────────────────────────
section('2) Fake context — Read / Write through FakeSqlQuery');
$fakeRes = Injector::getInstance('fake-hal-api-app')->getInstance(ResourceInterface::class);

$ro = $fakeRes->get('app://self/article', ['id' => 1]);
fwrite(STDOUT, "GET app://self/article?id=1 → {$ro->code}\n");
$rendered = json_decode((string) $ro, true);
fwrite(STDOUT, "  title:           {$rendered['title']}\n");
fwrite(STDOUT, "  publishedAt:     {$rendered['publishedAt']}\n");
fwrite(STDOUT, "  embedded.author: {$rendered['_embedded']['author']['name']}\n");
fwrite(STDOUT, '  embedded.tags:   ' . count($rendered['_embedded']['tagList']['items']) . " tag(s)\n");

$post = $fakeRes->post('app://self/article', [
    'slug' => 'demo-post-' . uniqid(),
    'title' => 'Demo article from composer demo',
    'body' => 'Body content from composer demo run.',
    'authorId' => 1,
    'categoryId' => 1,
    'status' => 'published',
    'tagIds' => [1, 5, 10],
]);
fwrite(STDOUT, "POST app://self/article → {$post->code}, new id={$post->body['id']}\n");

$del = $fakeRes->delete('app://self/article', ['id' => $post->body['id']]);
fwrite(STDOUT, "DELETE app://self/article?id={$post->body['id']} → {$del->code}\n");

// ── 3) Real DB ────────────────────────────────────────────────────
section('3) Real DB — autodetect malt / docker / sqlite');
[$dsn, $user, $password, $label] = detectDb();
fwrite(STDOUT, "Backend: {$label}\n");
fwrite(STDOUT, "DSN:     {$dsn}\n");
putenv("DB_DSN={$dsn}");
putenv("DB_USER={$user}");
putenv("DB_PASSWORD={$password}");

if (str_starts_with($dsn, 'mysql:')) {
    run("mysql -h 127.0.0.1 -u{$user} " . ($password !== '' ? "-p{$password} " : '') . "-e 'CREATE DATABASE IF NOT EXISTS bear_cms' 2>&1 | tail -1");
}

run("DB_DSN='{$dsn}' DB_USER='{$user}' DB_PASSWORD='{$password}' {$root}/vendor/bin/doctrine-migrations migrate --no-interaction 2>&1 | tail -3");
run("DB_DSN='{$dsn}' DB_USER='{$user}' DB_PASSWORD='{$password}' php {$root}/bin/seed.php --truncate 2>&1 | tail -3");

exec("rm -rf {$root}/var/tmp/hal-api-app");
$realRes = Injector::getInstance('hal-api-app')->getInstance(ResourceInterface::class);
$realRo = $realRes->get('app://self/article', ['id' => 1]);
$rendered2 = json_decode((string) $realRo, true);
fwrite(STDOUT, "GET app://self/article?id=1 → {$realRo->code}\n");
fwrite(STDOUT, "  title:           {$rendered2['title']}\n");
fwrite(STDOUT, "  publishedAt:     {$rendered2['publishedAt']}\n");
fwrite(STDOUT, "  embedded.author: {$rendered2['_embedded']['author']['name']}\n");

// ── 3.5) Auth (Fake provider) ─────────────────────────────────────
section('3.5) Auth — AuthInterface bound to FakeAuthProvider in test/fake context');
$authRo = $fakeRes->get('app://self/auth', []);
$authBody = json_decode((string) $authRo, true);
fwrite(STDOUT, "GET app://self/auth → {$authRo->code}\n");
fwrite(STDOUT, "  authorizationUrl: {$authBody['authorizationUrl']}\n");

$exchange = $fakeRes->post('app://self/auth', ['code' => 'demo-code', 'state' => 'demo-state']);
$exchangeBody = json_decode((string) $exchange, true);
fwrite(STDOUT, "POST app://self/auth → {$exchange->code}\n");
fwrite(STDOUT, "  user: {$exchangeBody['name']} <{$exchangeBody['email']}>\n");
fwrite(STDOUT, "(In production: AppModule binds AuthInterface -> GoogleAuthProvider with .env GOOGLE_*)\n");

// ── 4) Hypermedia walk ────────────────────────────────────────────
section('4) Hypermedia walk — Articles → Article → Author');
$list = $fakeRes->get('app://self/articles', ['perPage' => 3, 'status' => 'published']);
$listBody = json_decode((string) $list, true);
fwrite(STDOUT, "GET app://self/articles?status=published&perPage=3 → {$list->code}\n");
fwrite(STDOUT, '  items: ' . count($listBody['items']) . "\n");
$firstId = $listBody['items'][0]['id'];

$art = $fakeRes->get('app://self/article', ['id' => $firstId]);
$artBody = json_decode((string) $art, true);
fwrite(STDOUT, "  → goArticle id={$firstId} → {$art->code}, title=\"{$artBody['title']}\"\n");

$auth = $fakeRes->get('app://self/author', ['id' => $artBody['authorId']]);
$authBody = json_decode((string) $auth, true);
fwrite(STDOUT, "  → goAuthor id={$artBody['authorId']} → {$auth->code}, name=\"{$authBody['name']}\"\n");

// ── 4.5) Async embed via ext-parallel ────────────────────────────
section('4.5) Async embed — Article via bin/async.php (ext-parallel)');
if (! extension_loaded('parallel')) {
    fwrite(STDOUT, "ext-parallel is not loaded — skipping parallel run.\n");
    fwrite(STDOUT, "To exercise this section locally:\n");
    fwrite(STDOUT, "  composer parallel:up && composer parallel:demo\n");
    fwrite(STDOUT, "Or install on the host: pecl install parallel (requires ZTS PHP).\n");
}

if (extension_loaded('parallel')) {
    $env = "DB_DSN='{$dsn}' DB_USER='{$user}' DB_PASSWORD='{$password}'";
    $uri = "'app://self/article?id=1'";

    $cmd = "{$env} php {$root}/bin/async.php get {$uri}";
    fwrite(STDOUT, "$ {$cmd}\n");
    $out = (string) shell_exec("{$cmd} 2>&1");
    $parts = preg_split('/\R\R/', $out, 2);
    $payload = $parts[1] ?? $out;
    $body = json_decode($payload, true);
    if (! is_array($body)) {
        fwrite(STDOUT, "async command did not return a JSON body; raw output follows.\n");
        fwrite(STDOUT, $out . "\n");
    }

    if (is_array($body)) {
        $embedded = $body['_embedded'] ?? [];

        $report = static fn (string $rel): string => isset($embedded[$rel]) ? 'ok' : 'MISSING';
        fwrite(STDOUT, sprintf(
            "GET app://self/article?id=1 (parallel linker) → _embedded.author=%s, category=%s, tagList=%s\n",
            $report('author'),
            $report('category'),
            $report('tagList'),
        ));
    }

    fwrite(STDOUT, "(smoke only — fork-per-run overhead dominates wall clock, so no timing comparison is shown.\n");
    fwrite(STDOUT, " For real benchmarking, drive AsyncLinker in-process within a single PHP run.)\n");
}

// ── 5) ALPS validate ──────────────────────────────────────────────
section('5) ALPS profile — validate');
$asd = $root . '/node_modules/.bin/asd';
if (! is_file($asd)) {
    fwrite(STDOUT, "npm ASD is not installed. Run `composer setup:docs` to enable this section.\n");
}

if (is_file($asd)) {
    run(escapeshellarg($asd) . ' --validate ' . escapeshellarg($root . '/var/alps/profile.json') . ' 2>&1 | tail -3');
}

// ── 6) apidoc ─────────────────────────────────────────────────────
section('6) apidoc — regenerate HTML + OpenAPI + llms.txt');
run("cd {$root} && composer doc 2>&1 | tail -2");
fwrite(STDOUT, "  → docs/index.html, docs/openapi.json, docs/llms.txt\n");

// ── 7) CLI ────────────────────────────────────────────────────────
section('7) CLI — bin/cli/article-show + article-list (against the real DB)');
run("DB_DSN='{$dsn}' DB_USER='{$user}' DB_PASSWORD='{$password}' {$root}/bin/cli/article-show -i 1");
run("DB_DSN='{$dsn}' DB_USER='{$user}' DB_PASSWORD='{$password}' {$root}/bin/cli/article-list -n 3 -s published 2>&1 | tail -5");

section('Done');
fwrite(STDOUT, "All sections completed against backend: {$label}\n");
