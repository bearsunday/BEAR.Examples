<?php

declare(strict_types=1);

use MyVendor\Cms\Exception\BearAsyncNotInstalledException;

require dirname(__DIR__) . '/autoload.php';

$bootstrap = dirname(__DIR__) . '/vendor/bear/async/bootstrap.php';
if (! file_exists($bootstrap)) {
    throw new BearAsyncNotInstalledException(
        '"bear/async" is not installed. See https://github.com/bearsunday/BEAR.Async',
    );
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
