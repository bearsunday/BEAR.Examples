<?php

declare(strict_types=1);

namespace BEAR\Examples\Interceptor;

use BEAR\Resource\ResourceObject;

/**
 * Target resource for `SameOriginInterceptorTest::invocation()`. Lives in
 * its own file so phpstan can name the type without tripping
 * PSR-1 "one class per file".
 */
final class SameOriginInterceptorTestTarget extends ResourceObject
{
    public function onPost(): string
    {
        return SameOriginInterceptorTest::PROCEED_SENTINEL;
    }
}
