<?php

declare(strict_types=1);

/**
 * `composer demo:cache` — canonical QueryRepository cache showcase.
 *
 * Uses the fake context with an in-memory cache override, so it needs no DB and
 * leaves the main App resources untouched.
 */

use BEAR\QueryRepository\Header;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Transfer\HttpCacheInterface;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Module\CacheShowcaseModule;

require dirname(__DIR__) . '/autoload.php';

$demoLine = static fn (string $text = ''): int|false => fwrite(STDOUT, $text . PHP_EOL);

// QueryRepository can expose the same URI tag through parent and dependency
// paths; collapse duplicates so the demo prints the dependency set once.
$uniqueHeaderTokens = static fn (string $header): string => implode(' ', array_unique(array_filter(explode(' ', $header))));

$injector = Injector::getOverrideInstance('fake-hal-api-app', new CacheShowcaseModule());
$resource = $injector->getInstance(ResourceInterface::class);
$httpCache = $injector->getInstance(HttpCacheInterface::class);
assert($resource instanceof ResourceInterface);
assert($httpCache instanceof HttpCacheInterface);

$uri = 'app://self/cache/authorprofile';
$query = ['authorId' => 1];

$demoLine('CacheableResponse + explicit URI dependency');
$demoLine('-------------------------------------------');

$first = $resource->get($uri, $query);
$firstView = (string) $first;
$etag = (string) ($first->headers[Header::ETAG] ?? '');
$surrogateKey = (string) ($first->headers[Header::SURROGATE_KEY] ?? '');
$demoLine("GET {$uri}?authorId=1 => {$first->code}");
$demoLine('  ETag: ' . ($etag !== '' ? $etag : 'n/a'));
$demoLine('  Surrogate-Key: ' . ($surrogateKey !== '' ? $uniqueHeaderTokens($surrogateKey) : 'n/a'));
$demoLine('  304 candidate: ' . ($httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $etag]) ? 'yes' : 'no'));

$resource->put('app://self/cache/author', [
    'id' => 1,
    'name' => 'Cache Demo Author',
    'email' => 'cache-demo-author@example.com',
    'bio' => 'Updated through composer demo:cache.',
]);

$second = $resource->get($uri, $query);
$secondView = (string) $second;
$newEtag = (string) ($second->headers[Header::ETAG] ?? '');
$demoLine('');
$demoLine('After PUT app://self/cache/author?id=1');
$demoLine('  old ETag still valid: ' . ($httpCache->isNotModified([Header::HTTP_IF_NONE_MATCH => $etag]) ? 'yes' : 'no'));
$demoLine('  new ETag: ' . ($newEtag !== '' ? $newEtag : 'n/a'));
$demoLine('  view changed: ' . ($firstView !== $secondView ? 'yes' : 'no'));
