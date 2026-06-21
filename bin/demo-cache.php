<?php

declare(strict_types=1);

/**
 * `composer demo:cache` — QueryRepository cache showcase.
 *
 * Demonstrates the two cache patterns the project canonicalizes:
 *
 *   Section 1 — Embed only, automatic dependency (single-child)
 *     Cache\AuthorProfile composes Cache\Author via #[Embed]. The HAL
 *     renderer materializes the child into _embedded.author, and
 *     QueryRepository::setCacheDependency walks $ro->body for
 *     AbstractRequest children before HAL rendering, auto-merging the
 *     child's URI tag into the parent's Surrogate-Key. Zero lines of
 *     cache code in the parent class. PUT app://self/cache/author then
 *     cascades through to the parent's ETag.
 *
 *   Section 2 — fromAssoc for body-derived variable-length dependencies
 *     Cache\ArticleTags reads N tag rows from the DB and depends on N
 *     tag URIs that #[Embed] cannot statically express. Exactly one
 *     line: $this->headers[SURROGATE_KEY] = $uriTag->fromAssoc(...).
 *     PUT app://self/cache/tag?id={one-of-the-tags} cascades through.
 *
 * Both leaves (Cache\Author, Cache\Tag) are user-zero-code: #[Cacheable]
 * is the entire cache surface, and RefreshSameCommand handles purges on
 * write. The showcase uses CacheShowcaseModule to bind an in-memory
 * ArrayAdapter so the run is hermetic — no DB and no external cache.
 */

use BEAR\QueryRepository\Header;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use BEAR\Kata\Injector;
use BEAR\Kata\Module\CacheShowcaseModule;

require dirname(__DIR__) . '/autoload.php';

function section(string $title): void
{
    fwrite(STDOUT, "\n──────────────────────────────────────────────────────────\n");
    fwrite(STDOUT, "  {$title}\n");
    fwrite(STDOUT, "──────────────────────────────────────────────────────────\n");
}

function show(string $label, ResourceObject $ro): void
{
    $etag = $ro->headers[Header::ETAG] ?? '(none)';
    $surrogate = $ro->headers[Header::SURROGATE_KEY] ?? '(none)';
    fwrite(STDOUT, "  {$label}\n");
    fwrite(STDOUT, "    code:          {$ro->code}\n");
    fwrite(STDOUT, "    ETag:          {$etag}\n");
    fwrite(STDOUT, "    Surrogate-Key: {$surrogate}\n");
}

$injector = Injector::getOverrideInstance('fake-hal-api-app', new CacheShowcaseModule());
$resource = $injector->getInstance(ResourceInterface::class);
$httpCache = $injector->getInstance(HttpCacheInterface::class);

// ── Section 1 ────────────────────────────────────────────────────
section('1) Embed only — Cache\\AuthorProfile ← Cache\\Author (auto-merge)');
fwrite(STDOUT, "  User-written cache code in this section:\n");
fwrite(STDOUT, "    Cache\\Author        — 0 lines (#[Cacheable] only)\n");
fwrite(STDOUT, "    Cache\\AuthorProfile — 0 lines (#[Embed] only; auto-merged by setCacheDependency)\n");

$first = $resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
show('GET app://self/cache/authorprofile?authorId=1 (cold)', $first);
$oldEtag = $first->headers[Header::ETAG];

$cached = $resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
$hit = $httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]) ? 'HIT' : 'MISS';
fwrite(STDOUT, "  Repeated GET cache check: {$hit}\n");

fwrite(STDOUT, "  PUT app://self/cache/author?id=1 (write to the embedded child)\n");
$put = $resource->put('app://self/cache/author', [
    'id' => 1,
    'name' => 'Updated via demo:cache',
    'email' => 'updated.author@example.com',
    'bio' => 'Body updated by composer demo:cache.',
]);
fwrite(STDOUT, "    PUT code: {$put->code}\n");

$invalid = $httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldEtag]) ? 'still valid (BUG)' : 'invalidated';
fwrite(STDOUT, "  Old ETag is now: {$invalid}\n");

$second = $resource->get('app://self/cache/authorprofile', ['authorId' => 1]);
show('GET app://self/cache/authorprofile?authorId=1 (after PUT)', $second);
fwrite(STDOUT, '  ETag changed: ' . ($oldEtag !== $second->headers[Header::ETAG] ? 'yes' : 'no') . "\n");

// ── Section 2 ────────────────────────────────────────────────────
section('2) fromAssoc for body-variable dependency set — Cache\\ArticleTags ← Cache\\Tag[N]');
fwrite(STDOUT, "  User-written cache code in this section:\n");
fwrite(STDOUT, "    Cache\\Tag         — 0 lines (#[Cacheable] only)\n");
fwrite(STDOUT, "    Cache\\ArticleTags — 1 line (\$this->uriTag->fromAssoc(...))\n");

$firstTags = $resource->get('app://self/cache/articletags', ['articleId' => 3]);
show('GET app://self/cache/articletags?articleId=3 (cold)', $firstTags);
$oldTagsEtag = $firstTags->headers[Header::ETAG];
$body = json_decode((string) $firstTags, true, 512, JSON_THROW_ON_ERROR);
assert(is_array($body));
$tagItems = $body['items'];
fwrite(STDOUT, '    items: ' . count($tagItems) . " tag(s)\n");

$cachedTags = $resource->get('app://self/cache/articletags', ['articleId' => 3]);
$hit = $httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldTagsEtag]) ? 'HIT' : 'MISS';
fwrite(STDOUT, "  Repeated GET cache check: {$hit}\n");

$pickedTag = $tagItems[0];
fwrite(STDOUT, "  PUT app://self/cache/tag?id={$pickedTag['id']} (write to a dependency)\n");
$putTag = $resource->put('app://self/cache/tag', [
    'id' => $pickedTag['id'],
    'slug' => 'demo-edit-' . $pickedTag['slug'],
    'name' => 'Demo Edit: ' . $pickedTag['name'],
]);
fwrite(STDOUT, "    PUT code: {$putTag->code}\n");

$invalid = $httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $oldTagsEtag]) ? 'still valid (BUG)' : 'invalidated';
fwrite(STDOUT, "  Old ETag is now: {$invalid}\n");

$secondTags = $resource->get('app://self/cache/articletags', ['articleId' => 3]);
show('GET app://self/cache/articletags?articleId=3 (after PUT)', $secondTags);
fwrite(STDOUT, '  ETag changed: ' . ($oldTagsEtag !== $secondTags->headers[Header::ETAG] ? 'yes' : 'no') . "\n");

section('Done');
fwrite(STDOUT, "Backend: in-memory ArrayAdapter via CacheShowcaseModule (hermetic — no DB, no external cache).\n");
fwrite(STDOUT, "See docs/conventions.md § Cache for the canonical pattern.\n");
