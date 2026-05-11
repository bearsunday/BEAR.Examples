#!/usr/bin/env bash
# Install ext-parallel against the ZTS PHP used by composer demo:async-local.
# Idempotent: exits 0 if parallel is already loaded.
set -euo pipefail

PHP_ZTS="${PHP_ZTS:-/opt/homebrew/opt/php-zts/bin/php}"

if [[ ! -x "$PHP_ZTS" ]]; then
    cat >&2 <<EOF
error: ZTS PHP not found at $PHP_ZTS

Install it first:
  brew install shivammathur/php/php-zts

Or set PHP_ZTS=/path/to/zts/php and re-run.
EOF
    exit 1
fi

MODULES="$("$PHP_ZTS" -m 2>/dev/null)"
if printf '%s\n' "$MODULES" | grep -qi '^parallel$'; then
    echo "ext-parallel already loaded in $("$PHP_ZTS" -v 2>/dev/null | head -n1)"
    exit 0
fi

# Locate pecl/pear for the ZTS install. Shivam Mathur's php-zts ships them
# alongside the binary; fall back to PATH if necessary.
PHP_ZTS_DIR="$(dirname "$PHP_ZTS")"
PECL="$PHP_ZTS_DIR/pecl"
if [[ ! -x "$PECL" ]]; then
    PECL="$(command -v pecl || true)"
fi
if [[ -z "$PECL" || ! -x "$PECL" ]]; then
    echo "error: pecl not found for $PHP_ZTS" >&2
    exit 1
fi

echo "Installing ext-parallel via $PECL ..."
# Force reinstall to ensure the .so is rebuilt against the current ZTS PHP.
printf "\n" | "$PECL" install --force parallel

# Shivam Mathur's php-zts ships extension_dir as <prefix>/lib/php/pecl/<api>-zts,
# but pecl drops the .so in <cellar>/pecl/<api>-zts. Bridge the two with a
# symlink if necessary.
EXT_DIR="$("$PHP_ZTS" -r 'echo ini_get("extension_dir");' 2>/dev/null || true)"
SO_NAME="parallel.so"
if [[ -n "$EXT_DIR" && ! -f "$EXT_DIR/$SO_NAME" ]]; then
    CELLAR_SO="$(/usr/bin/find /opt/homebrew/Cellar/php-zts -name "$SO_NAME" -print -quit 2>/dev/null || true)"
    if [[ -n "$CELLAR_SO" ]]; then
        mkdir -p "$EXT_DIR"
        ln -sf "$CELLAR_SO" "$EXT_DIR/$SO_NAME"
        echo "Symlinked $CELLAR_SO → $EXT_DIR/$SO_NAME"
    fi
fi

echo
echo "Verifying ..."
MODULES_POST="$("$PHP_ZTS" -m 2>/dev/null)"
printf '%s\n' "$MODULES_POST" | grep -qi '^parallel$' && echo "ext-parallel installed."
