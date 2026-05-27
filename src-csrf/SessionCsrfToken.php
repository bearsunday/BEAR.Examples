<?php

declare(strict_types=1);

namespace Ray\Csrf;

use Override;

use function bin2hex;
use function hash_equals;
use function is_string;
use function random_bytes;
use function session_start;
use function session_status;

use const PHP_SESSION_ACTIVE;

/** @SuppressWarnings("PHPMD.Superglobals") Session adapter boundary. */
final class SessionCsrfToken implements CsrfTokenInterface
{
    private const string SESSION_KEY = 'ray_csrf_token';

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
        if (! is_string($stored) || $stored === '' || $candidate === '') {
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
