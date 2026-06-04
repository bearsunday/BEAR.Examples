<?php

declare(strict_types=1);

chdir(dirname(__DIR__));

passthru('rm -rf ./var/tmp/*', $clearStatus);
if ($clearStatus !== 0) {
    exit($clearStatus);
}

$npm = trim((string) shell_exec('command -v npm 2>/dev/null'));
if ($npm === '') {
    fwrite(STDOUT, "npm is not available; skipping npm ASD install. Run `npm install` before `composer doc`.\n");

    exit(0);
}

$install = file_exists('package-lock.json') ? 'ci' : 'install';
$command = escapeshellarg($npm) . " {$install} --ignore-scripts --no-audit --no-fund";
passthru($command, $npmStatus);
if ($npmStatus !== 0) {
    fwrite(STDERR, "npm dependency install failed. Run `npm install` before `composer doc`.\n");

    exit($npmStatus);
}
