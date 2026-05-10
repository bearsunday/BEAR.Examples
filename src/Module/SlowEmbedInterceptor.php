<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

use function usleep;

final readonly class SlowEmbedInterceptor implements MethodInterceptor
{
    public function __construct(
        private int $delayUs = 150_000,
    ) {
    }

    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        usleep($this->delayUs);

        return $invocation->proceed();
    }
}
