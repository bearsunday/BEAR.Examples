<?php

declare(strict_types=1);

require dirname(__DIR__) . '/autoload.php';

$bootstrap = dirname(__DIR__) . '/vendor/bear/async/bootstrap.php';
if (! file_exists($bootstrap)) {
    throw new LogicException('"bear/async" is not installed.');
}

$context = getenv('APP_CONTEXT') ?: (PHP_SAPI === 'cli' ? 'cli-hal-api-app' : 'hal-api-app');

exit((require $bootstrap)(
    $context,
    'MyVendor\Cms',
    dirname(__DIR__),
    $GLOBALS,
    $_SERVER,
));
