<?php

declare(strict_types=1);

namespace Ray\Csrf\Interceptor;

use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Csrf\CsrfTokenInterface;
use Ray\Csrf\Exception\ForbiddenException;
use Ray\Csrf\Http\AllowedOrigin;
use Ray\Csrf\Http\RequestBodyTokenInterface;

/** Synchroniser-token gate. See the consumer's CSRF design notes. */
final readonly class CsrfTokenInterceptor implements MethodInterceptor
{
    public function __construct(
        private CsrfTokenInterface $csrf,
        private RequestBodyTokenInterface $body,
        private AllowedOrigin $allowedOrigin,
    ) {
    }

    /** @param MethodInvocation<object> $invocation */
    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        // Same on/off knob as SameOriginInterceptor — disabled when no
        // browser is on the other side (dev / CLI / test).
        if ($this->allowedOrigin->value === null) {
            return $invocation->proceed();
        }

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
