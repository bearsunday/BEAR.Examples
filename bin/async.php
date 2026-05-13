<?php

declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

$bootstrap = dirname(__DIR__) . '/vendor/bear/async/bootstrap.php';
if (! file_exists($bootstrap)) {
    fwrite(STDERR, '"bear/async" is not installed. See https://github.com/bearsunday/BEAR.Async' . PHP_EOL);
    exit(1);
}

$defaultContext = PHP_SAPI === 'cli' ? 'cli-hal-api-app' : 'hal-api-app';
$context = getenv('APP_CONTEXT') ?: $defaultContext;

exit((require $bootstrap)(
    $context,
    'MyVendor\Cms',
    dirname(__DIR__),
    $GLOBALS,
    $_SERVER,
));
