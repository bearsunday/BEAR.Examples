<?php

declare(strict_types=1);

use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Application\AppInterface;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Module\App;

require dirname(__DIR__) . '/autoload.php';

function section(string $title): void
{
    fwrite(STDOUT, "\n{$title}\n");
    fwrite(STDOUT, str_repeat('-', strlen($title)) . "\n");
}

function resource(string $context): ResourceInterface
{
    $app = Injector::getInstance($context)->getInstance(AppInterface::class);
    assert($app instanceof App);

    return $app->resource;
}

function measureArticle(ResourceInterface $resource): array
{
    $start = hrtime(true);
    $ro = $resource->get('app://self/article', ['id' => 1]);
    $view = (string) $ro;
    $elapsedMs = (hrtime(true) - $start) / 1_000_000;
    $data = json_decode($view, true);
    assert(is_array($data));

    return [$elapsedMs, $data];
}

if (! extension_loaded('parallel')) {
    fwrite(STDOUT, "ext-parallel is not loaded in this PHP runtime.\n");
    fwrite(STDOUT, "Run with Docker:\n");
    fwrite(STDOUT, "  docker compose run --rm php-async composer demo:async\n");

    exit(0);
}

section('BEAR.Async Article embed demo');
fwrite(STDOUT, "Resource code: unchanged Article::onGet with three #[Embed] dependencies.\n");
fwrite(STDOUT, "Timing context: fake data + demo-only 150ms delay on Author/Category/Tags.\n");

$sync = resource('slow-fake-hal-api-app');
$async = resource('async-slow-fake-hal-api-app');

[$syncMs, $syncData] = measureArticle($sync);
[$warmupMs] = measureArticle($async);
[$asyncMs, $asyncData] = measureArticle($async);

section('Timing');
printf("sync:         %.2f ms\n", $syncMs);
printf("async warmup: %.2f ms\n", $warmupMs);
printf("async:        %.2f ms\n", $asyncMs);
printf("speedup:      %.2fx\n", $syncMs / $asyncMs);

section('Verification');
ksort($syncData);
ksort($asyncData);
fwrite(STDOUT, 'same HAL representation: ' . ($syncData === $asyncData ? 'yes' : 'no') . "\n");
fwrite(STDOUT, 'embedded resources: ' . count($asyncData['_embedded'] ?? []) . "\n");

exit($syncData === $asyncData && $syncMs > $asyncMs ? 0 : 1);
