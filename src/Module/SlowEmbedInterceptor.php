<?php

declare(strict_types=1);

namespace MyVendor\Cms\Module;

use Override;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use Ray\Di\Di\Named;

use function usleep;

final readonly class SlowEmbedInterceptor implements MethodInterceptor
{
    public function __construct(
        #[Named('slow_embed_delay_us')]
        private int $delayUs,
    ) {
    }

    #[Override]
    public function invoke(MethodInvocation $invocation): mixed
    {
        usleep($this->delayUs);

        return $invocation->proceed();
    }
}
