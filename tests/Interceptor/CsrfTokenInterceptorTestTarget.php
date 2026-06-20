<?php

declare(strict_types=1);

namespace BEAR\Examples\Interceptor;

use BEAR\Resource\ResourceObject;

/**
 * Target resource for `CsrfTokenInterceptorTest::invocation()`. Lives
 * in its own file so phpstan can name the type without tripping
 * PSR-1 "one class per file". Mirrors the same workaround used by
 * `SameOriginInterceptorTestTarget`.
 */
final class CsrfTokenInterceptorTestTarget extends ResourceObject
{
    public function onPost(): string
    {
        return CsrfTokenInterceptorTest::PROCEED_SENTINEL;
    }
}
