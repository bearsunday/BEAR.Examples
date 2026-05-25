<?php

declare(strict_types=1);

namespace MyVendor\Cms\Interceptor;

use MyVendor\Cms\Auth\CsrfTokenInterface;
use MyVendor\Cms\Exception\ForbiddenException;
use MyVendor\Cms\Http\RequestBodyTokenInterface;
use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

/** Synchroniser-token gate. See `docs/journal/csrf-design.md`. */
final readonly class CsrfTokenInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfTokenInterface $csrf,
        private RequestBodyTokenInterface $body,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        $submitted = $this->body->submitted();
        if ($submitted === null) {
            throw new ForbiddenException('CSRF token missing.');
        }

        if (! $this->csrf->verify($submitted)) {
            throw new ForbiddenException('CSRF token invalid.');
        }

        return $invocation->proceed();
    }
}
