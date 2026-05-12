<?php

declare(strict_types=1);

use BEAR\Async\PendingRequests;
use BEAR\Async\Module\ParallelRuntimeModule;
use BEAR\Resource\ResourceInterface;
use BEAR\Sunday\Extension\Application\AppInterface;
use MyVendor\Cms\Injector;
use MyVendor\Cms\Module\App;

require dirname(__DIR__) . '/autoload.php';

(static function (): void {
    $section = static function (string $title): void {
        fwrite(STDOUT, "\n{$title}\n");
        fwrite(STDOUT, str_repeat('-', strlen($title)) . "\n");
    };

    $syncResource = static function (string $context): ResourceInterface {
        $app = Injector::getInstance($context)->getInstance(AppInterface::class);
        assert($app instanceof App);

        return $app->resource;
    };

    /** @return array{0: ResourceInterface, 1: PendingRequests} */
    $asyncRuntime = static function (string $context): array {
        $injector = Injector::getOverrideInstance($context, new ParallelRuntimeModule($context));
        $app = $injector->getInstance(AppInterface::class);
        assert($app instanceof App);

        return [$app->resource, $injector->getInstance(PendingRequests::class)];
    };

    $measureArticle = static function (ResourceInterface $resource, PendingRequests|null $pendingRequests = null): array {
        $pendingRequests?->reset();

        $start = hrtime(true);
        $ro = $resource->get('app://self/article', ['id' => 1]);
        $view = (string) $ro;
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;
        $data = json_decode($view, true);
        assert(is_array($data));

        return [$elapsedMs, $data];
    };

    if (! extension_loaded('parallel')) {
        fwrite(STDOUT, "ext-parallel is not loaded in this PHP runtime.\n");
        fwrite(STDOUT, "Run with Docker:\n");
        fwrite(STDOUT, "  composer docker:async-demo\n");

        exit(0);
    }

    $section('BEAR.Async Article embed demo');
    fwrite(STDOUT, "Resource code: unchanged Article::onGet with three #[Embed] dependencies.\n");
    fwrite(STDOUT, "Timing context: fake data + demo-only 150ms delay on Author/Category/Tags.\n");

    $context = 'slow-fake-hal-api-app';
    $sync = $syncResource($context);
    [$async, $pendingRequests] = $asyncRuntime($context);

    [$syncMs, $syncData] = $measureArticle($sync);
    [$warmupMs] = $measureArticle($async, $pendingRequests);
    [$asyncMs, $asyncData] = $measureArticle($async, $pendingRequests);

    $section('Timing');
    printf("sync:         %.2f ms\n", $syncMs);
    printf("async warmup: %.2f ms\n", $warmupMs);
    printf("async:        %.2f ms\n", $asyncMs);
    printf("speedup:      %.2fx\n", $syncMs / $asyncMs);

    $section('Verification');
    ksort($syncData);
    ksort($asyncData);
    $embeddedCount = is_array($asyncData['_embedded'] ?? null) ? count($asyncData['_embedded']) : 0;
    fwrite(STDOUT, 'same HAL representation: ' . ($syncData === $asyncData ? 'yes' : 'no') . "\n");
    fwrite(STDOUT, 'embedded resources: ' . $embeddedCount . "\n");

    exit($syncData === $asyncData && $embeddedCount === 3 ? 0 : 1);
})();
