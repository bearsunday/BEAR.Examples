#!/usr/bin/env bash
# Run a command under the ZTS PHP (required for BEAR.Async parallel #[Embed]).
# Usage: bin/php-zts.sh <script> [args...]
set -euo pipefail

PHP_ZTS="${PHP_ZTS:-/opt/homebrew/opt/php-zts/bin/php}"

if [[ ! -x "$PHP_ZTS" ]]; then
    cat >&2 <<EOF
error: ZTS PHP not found at $PHP_ZTS

Install it first:
  brew install shivammathur/php/php-zts
  composer php-zts:install

Or set PHP_ZTS=/path/to/zts/php and re-run.
EOF
    exit 1
fi

MODULES="$("$PHP_ZTS" -m 2>/dev/null)"
if ! printf '%s\n' "$MODULES" | grep -qi '^parallel$'; then
    cat >&2 <<EOF
error: ext-parallel is not loaded in $PHP_ZTS

Install it with:
  composer php-zts:install
EOF
    exit 1
fi

exec "$PHP_ZTS" "$@"
