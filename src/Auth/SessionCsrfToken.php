<?php

declare(strict_types=1);

namespace MyVendor\Cms\Auth;

use Override;

use function bin2hex;
use function hash_equals;
use function is_string;
use function random_bytes;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

/**
 * `$_SESSION`-backed `CsrfTokenInterface`.
 *
 * Mirrors `NativeAuthSession`'s boundary discipline — `$_SESSION` is
 * touched here and only here for CSRF. Token entropy: 32 bytes from
 * `random_bytes` → 64-char hex (256 bits). Verification uses
 * `hash_equals` so timing leaks don't reveal partial matches.
 *
 * Token lifetime is per-session by design; see the interface docblock
 * for why per-request rotation isn't done.
 *
 * @SuppressWarnings("PHPMD.Superglobals") Session adapter boundary; mirrors `NativeAuthSession`.
 */
final class SessionCsrfToken implements CsrfTokenInterface
{
    private const string SESSION_KEY = 'cms_csrf_token';

    #[Override]
    public function issue(): string
    {
        $this->start();

        $existing = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_string($existing) && $existing !== '') {
            return $existing;
        }

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;

        return $token;
    }

    #[Override]
    public function verify(string $candidate): bool
    {
        $this->start();

        $stored = $_SESSION[self::SESSION_KEY] ?? null;
        if (! is_string($stored) || $stored === '') {
            // No token was ever issued — refuse the equality check so a
            // blank-submitted form on a fresh session can't slip through.
            return false;
        }

        if ($candidate === '') {
            return false;
        }

        return hash_equals($stored, $candidate);
    }

    #[Override]
    public function clear(): void
    {
        $this->start();
        unset($_SESSION[self::SESSION_KEY]);
    }

    private function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_start();
    }
}
