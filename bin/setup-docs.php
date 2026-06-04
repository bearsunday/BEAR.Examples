<?php

declare(strict_types=1);

chdir(dirname(__DIR__));

if (is_file('node_modules/.bin/asd')) {
    exit(0);
}

$npm = trim((string) shell_exec('command -v npm 2>/dev/null'));
if ($npm === '') {
    fwrite(STDERR, "npm is required for `composer doc` because ALPS diagrams use npm ASD.\n");

    exit(1);
}

$install = file_exists('package-lock.json') ? 'ci' : 'install';
$command = escapeshellarg($npm) . " {$install} --ignore-scripts --no-audit --no-fund";
passthru($command, $npmStatus);
if ($npmStatus !== 0) {
    fwrite(STDERR, "npm dependency install failed. Run `composer setup:docs` before `composer doc`.\n");

    exit($npmStatus);
}
